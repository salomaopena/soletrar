<?php

declare(strict_types=1);

namespace App\Services\Notificacoes;

use App\Services\Notificacoes\Canais\CanalSistema;
use CodeIgniter\Database\ConnectionInterface;

/**
 * FACHADA ÚNICA de notificações — o resto da aplicação só conhece isto.
 *
 * Fluxo: notificar(evento, destinatários, dados)
 *   1. resolve os templates ativos do evento (convenção {evento}_{canal});
 *   2. renderiza os placeholders;
 *   3. canal 'sistema' → escrita direta (síncrono, sem provedor externo);
 *   4. canais 'email'/'sms' → FilaService (assíncrono, com retries).
 *
 * Um evento SEM template ativo num canal simplesmente não envia nesse
 * canal — desligar um tipo de SMS é desativar o template no backoffice,
 * sem deploy.
 */
final class Notificador
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly TemplateRenderer $renderer,
        private readonly FilaService $fila,
        private readonly CanalSistema $canalSistema,
    ) {
    }

    /**
     * Notifica destinatários externos (encarregados, subscritores...).
     *
     * @param array $destinatarios ['email' => ?, 'telefone' => ?, 'user_id' => ?]
     * @param array $dados         Valores dos {{placeholders}}
     */
    public function notificar(string $evento, array $destinatarios, array $dados, int $prioridade = 5): void
    {
        $templates = $this->renderer->templatesDoEvento($evento);

        if ($templates === []) {
            log_message('warning', 'Notificador: evento "{ev}" sem templates ativos.', ['ev' => $evento]);

            return;
        }

        foreach ($templates as $canal => $template) {
            $render = $this->renderer->renderizar($template, $dados);

            match (true) {
                $canal === 'sistema' && ! empty($destinatarios['user_id']) =>
                    $this->canalSistema->criar(
                        (int) $destinatarios['user_id'],
                        $evento,
                        $render['assunto'] ?? $template['nome'],
                        $render['corpo'],
                        $dados['link'] ?? null,
                    ),

                $canal === 'email' && ! empty($destinatarios['email']) =>
                    $this->fila->enfileirar(
                        'email', $destinatarios['email'], $render['corpo'], $render['assunto'],
                        (int) $template['id'],
                        $dados + ['template_codigo' => $template['codigo']],
                        $destinatarios['user_id'] ?? null,
                        $prioridade,
                    ),

                $canal === 'sms' && ! empty($destinatarios['telefone']) =>
                    $this->fila->enfileirar(
                        'sms', $destinatarios['telefone'], $render['corpo'], null,
                        (int) $template['id'],
                        $dados + ['template_codigo' => $template['codigo']],
                        $destinatarios['user_id'] ?? null,
                        $prioridade,
                    ),

                default => null,   // canal sem destinatário correspondente: ignora
            };
        }
    }

    /**
     * Notifica um utilizador interno (resolve e-mail/telefone do perfil).
     *
     * Aceita int|string porque IDs vindos da BD ou de entities podem
     * chegar como string — normaliza-se aqui, em vez de rebentar.
     */
    public function notificarUtilizador(int|string $userId, string $evento, array $dados, int $prioridade = 5): void
    {
        $userId = (int) $userId;

        if ($userId <= 0) {
            return;
        }

        $contactos = $this->db->query(
            'SELECT ai.secret AS email, pu.telefone
               FROM users u
          LEFT JOIN auth_identities ai
                 ON ai.user_id = u.id AND ai.type = "email_password"
          LEFT JOIN perfis_utilizador pu ON pu.user_id = u.id
              WHERE u.id = ?',
            [$userId]
        )->getRowArray() ?? [];

        $this->notificar($evento, [
            'user_id'  => $userId,
            'email'    => $contactos['email'] ?? null,
            'telefone' => $contactos['telefone'] ?? null,
        ], $dados, $prioridade);
    }

    /** Notifica todos os membros de um grupo Shield (só canal sistema + email). */
    public function notificarGrupo(string $grupo, string $evento, array $dados): void
    {
        $userIds = $this->db->table('auth_groups_users')
            ->select('user_id')->where('group', $grupo)
            ->get()->getResultArray();

        foreach ($userIds as $linha) {
            $this->notificarUtilizador((int) $linha['user_id'], $evento, $dados);
        }
    }

    /**
     * Resultados homologados de um evento → encarregado principal de
     * cada participante classificado (chamado pela homologação, Fase 6).
     */
    public function notificarResultadosEvento(int $eventoId): void
    {
        $participantes = $this->db->query(
            'SELECT pa.posicao_final, c.nome_completo AS candidato_nome,
                    ev.nome AS evento_nome, ee.email, ee.telefone
               FROM participacoes pa
               JOIN eventos_competicao ev ON ev.id = pa.evento_id
               JOIN inscricoes i  ON i.id = pa.inscricao_id
               JOIN candidatos c  ON c.id = i.candidato_id
          LEFT JOIN encarregados_educacao ee
                 ON ee.candidato_id = c.id AND ee.principal = 1
              WHERE pa.evento_id = ? AND pa.posicao_final IS NOT NULL',
            [$eventoId]
        )->getResultArray();

        foreach ($participantes as $p) {
            $this->notificar('resultado_publicado', [
                'email'    => $p['email'],
                'telefone' => $p['telefone'],
            ], [
                'candidato_nome'  => $p['candidato_nome'],
                'evento_nome'     => $p['evento_nome'],
                'posicao'         => $p['posicao_final'],
                'link_resultados' => site_url('resultados/evento/' . $eventoId),
            ], prioridade: 3);   // resultados têm prioridade acima do normal
        }
    }
}
