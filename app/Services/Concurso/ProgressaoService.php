<?php

declare(strict_types=1);

namespace App\Services\Concurso;

use CodeIgniter\Database\ConnectionInterface;
use RuntimeException;

/**
 * Progressão entre fases (RN-04) — alimenta a tabela progressoes_fase
 * introduzida na v2.0 do banco: cada qualificação é um registo
 * auditável (quem, quando, de onde, para onde, em que posição).
 */
final class ProgressaoService
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /**
     * Após homologação: qualifica os N primeiros (vagas_proxima_fase) do
     * evento para a fase seguinte da mesma edição.
     */
    public function apurarQualificados(int $eventoId, int $aprovadoPor): int
    {
        $contexto = $this->contextoDoEvento($eventoId);

        if ($contexto->fase_seguinte_id === null || (int) $contexto->vagas === 0) {
            return 0; // final nacional: não há para onde progredir
        }

        $qualificados = $this->db->table('participacoes')
            ->select('inscricao_id, posicao_final')
            ->where('evento_id', $eventoId)
            ->where('posicao_final IS NOT NULL')
            ->where('posicao_final <=', (int) $contexto->vagas)
            ->get()->getResultArray();

        $total = 0;

        foreach ($qualificados as $q) {
            // ignore(true): a UNIQUE (inscricao_id, fase_destino_id) torna
            // a operação idempotente — reprocessar não duplica.
            $this->db->table('progressoes_fase')->ignore(true)->insert([
                'inscricao_id'         => $q['inscricao_id'],
                'fase_origem_id'       => $contexto->fase_id,
                'evento_origem_id'     => $eventoId,
                'fase_destino_id'      => $contexto->fase_seguinte_id,
                'tipo'                 => 'qualificacao_direta',
                'posicao_qualificacao' => $q['posicao_final'],
                'aprovada_por'         => $aprovadoPor,
                'created_at'           => utc_agora(),
            ]);
            $total += $this->db->affectedRows();
        }

        return $total;
    }

    /**
     * Repescagem/substituição manual (ex.: qualificado desiste).
     * Exige coordenador com concurso.resultados.homologar; fica registada
     * com tipo próprio e observação obrigatória.
     */
    public function progredirManual(
        int $inscricaoId,
        int $faseOrigemId,
        int $faseDestinoId,
        string $tipo,
        string $observacao,
        int $aprovadoPor,
    ): void {
        if (! in_array($tipo, ['repescagem', 'convite', 'substituicao'], true)) {
            throw new RuntimeException('Tipo de progressão manual inválido.');
        }
        if (trim($observacao) === '') {
            throw new RuntimeException(lang('Concurso.observacaoObrigatoria'));
        }

        $this->db->table('progressoes_fase')->insert([
            'inscricao_id'    => $inscricaoId,
            'fase_origem_id'  => $faseOrigemId,
            'fase_destino_id' => $faseDestinoId,
            'tipo'            => $tipo,
            'observacoes'     => $observacao,
            'aprovada_por'    => $aprovadoPor,
            'created_at'      => utc_agora(),
        ]);
    }

    /** Fase seguinte = mesma edição, ordem imediatamente superior. */
    private function contextoDoEvento(int $eventoId): object
    {
        $linha = $this->db->query(
            'SELECT ev.fase_id, f.vagas_proxima_fase AS vagas,
                    (SELECT f2.id FROM fases_concurso f2
                      WHERE f2.edicao_id = f.edicao_id AND f2.ordem > f.ordem
                      ORDER BY f2.ordem ASC LIMIT 1) AS fase_seguinte_id
               FROM eventos_competicao ev
               JOIN fases_concurso f ON f.id = ev.fase_id
              WHERE ev.id = ?',
            [$eventoId]
        )->getRow();

        return $linha ?? throw new RuntimeException(lang('Concurso.eventoNaoEncontrado'));
    }
}
