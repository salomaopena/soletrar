<?php

declare(strict_types=1);

namespace App\Services\Notificacoes\Canais;

use CodeIgniter\Database\ConnectionInterface;
use Config\Notificacoes as NotificacoesConfig;
use Throwable;

/**
 * Canal de e-mail: CI4 Email (SMTP configurado em Config\Email/.env)
 * + registo OBRIGATÓRIO em logs_email, sucesso ou falha.
 *
 * O corpo é envolvido no layout HTML institucional
 * (Views/emails/layout_base.php) no momento do envio.
 */
final class CanalEmail implements CanalInterface
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly NotificacoesConfig $config,
    ) {
    }

    public function enviar(array $item): bool
    {
        $email = service('email');   // instância fresca por envio

        $email->setFrom($this->config->emailRemetente, $this->config->emailRemetenteNome);
        $email->setTo($item['destinatario']);
        $email->setSubject($item['assunto'] ?? '');
        $email->setMessage(view('emails/layout_base', [
            'conteudo' => nl2br(esc($item['corpo'])),
            'assunto'  => $item['assunto'] ?? '',
        ]));
        $email->setMailType('html');

        $erro = null;

        try {
            $sucesso = $email->send(false);   // false: não limpa, para ler o debugger
            if (! $sucesso) {
                $erro = strip_tags($email->printDebugger(['headers']));
            }
        } catch (Throwable $e) {
            $sucesso = false;
            $erro    = $e->getMessage();
        }

        // Log obrigatório — auditoria e diagnóstico de entregabilidade.
        $this->db->table('logs_email')->insert([
            'fila_id'         => $item['id'] ?? null,
            'user_id'         => $item['user_id'] ?? null,
            'destinatario'    => $item['destinatario'],
            'assunto'         => $item['assunto'] ?? '',
            'template_codigo' => $item['dados_json']['template_codigo'] ?? null,
            'provider'        => 'smtp',
            'status'          => $sucesso ? 'enviado' : 'falhado',
            'erro'            => $erro !== null ? mb_substr($erro, 0, 2000) : null,
            'created_at'      => utc_agora(),
        ]);

        return $sucesso;
    }
}
