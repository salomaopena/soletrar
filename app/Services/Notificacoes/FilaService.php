<?php

declare(strict_types=1);

namespace App\Services\Notificacoes;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;
use Config\Notificacoes as NotificacoesConfig;

/**
 * Fila de envio (tabela notificacoes_fila, v2.0).
 *
 * Ciclo de vida: pendente → a_enviar (reclamada) → enviada
 *                                   └→ pendente com backoff → ... → falhada
 *
 * A reclamação do lote é ATÓMICA (UPDATE condicional): com vários
 * workers em paralelo, nenhuma mensagem é processada duas vezes.
 */
final class FilaService
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly NotificacoesConfig $config,
    ) {
    }

    /** Coloca uma mensagem na fila. Devolve o ID. */
    public function enfileirar(
        string $canal,
        string $destinatario,
        string $corpo,
        ?string $assunto = null,
        ?int $templateId = null,
        array $dados = [],
        ?int $userId = null,
        int $prioridade = 5,
    ): int {
        $this->db->table('notificacoes_fila')->insert([
            'canal'          => $canal,
            'user_id'        => $userId,
            'destinatario'   => $destinatario,
            'template_id'    => $templateId,
            'assunto'        => $assunto,
            'corpo'          => $corpo,
            'dados_json'     => json_encode($dados, JSON_UNESCAPED_UNICODE),
            'prioridade'     => $prioridade,
            'status'         => 'pendente',
            'max_tentativas' => $this->config->maxTentativas,
            'created_at'     => utc_agora(),
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Reclama e devolve o próximo lote a processar.
     * Passo 1: marca até N pendentes vencidas como 'a_enviar' com uma
     * etiqueta única deste worker; passo 2: lê as que etiquetou.
     */
    public function reclamarLote(?int $tamanho = null): array
    {
        $tamanho  = $tamanho ?? $this->config->tamanhoLote;
        $etiqueta = bin2hex(random_bytes(8));
        $agora    = Time::now('UTC')->toDateTimeString();

        // MySQL permite UPDATE ... ORDER BY ... LIMIT: reclamação atómica.
        $this->db->query(
            "UPDATE notificacoes_fila
                SET status = 'a_enviar', erro_ultimo = ?, updated_at = ?
              WHERE status = 'pendente'
                AND (proxima_tentativa_em IS NULL OR proxima_tentativa_em <= ?)
              ORDER BY prioridade ASC, id ASC
              LIMIT {$tamanho}",
            ['claim:' . $etiqueta, $agora, $agora]
        );

        return $this->db->table('notificacoes_fila')
            ->where('status', 'a_enviar')
            ->where('erro_ultimo', 'claim:' . $etiqueta)
            ->get()->getResultArray();
    }

    public function marcarEnviada(int $filaId): void
    {
        $this->db->table('notificacoes_fila')->where('id', $filaId)->update([
            'status'      => 'enviada',
            'erro_ultimo' => null,
            'enviada_em'  => Time::now('UTC')->toDateTimeString(),
            'updated_at'  => utc_agora(),
        ]);
    }

    /** Falha → reagenda com backoff, ou marca 'falhada' se esgotou. */
    public function marcarFalha(array $item, string $erro): void
    {
        $tentativas = (int) $item['tentativas'] + 1;

        if ($tentativas >= (int) $item['max_tentativas']) {
            $this->db->table('notificacoes_fila')->where('id', $item['id'])->update([
                'status'      => 'falhada',
                'tentativas'  => $tentativas,
                'erro_ultimo' => $erro,
                'updated_at'  => utc_agora(),
            ]);

            log_message('error', 'Fila #{id} FALHADA definitivamente ({canal} → {dest}): {erro}', [
                'id' => $item['id'], 'canal' => $item['canal'],
                'dest' => $item['destinatario'], 'erro' => $erro,
            ]);

            return;
        }

        $backoff = $this->config->backoffSegundos[$tentativas - 1]
            ?? end($this->config->backoffSegundos);

        $this->db->table('notificacoes_fila')->where('id', $item['id'])->update([
            'status'               => 'pendente',
            'tentativas'           => $tentativas,
            'erro_ultimo'          => $erro,
            'proxima_tentativa_em' => Time::now('UTC')->addSeconds($backoff)->toDateTimeString(),
            'updated_at'           => utc_agora(),
        ]);
    }
}
