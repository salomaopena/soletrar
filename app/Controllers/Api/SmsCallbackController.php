<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;

/**
 * Webhook de relatórios de entrega (DLR) da pro2sms.
 *
 * Configurar no painel do provedor:
 *   POST https://<dominio>/api/sms/callback?token=<CALLBACK_TOKEN do .env>
 *
 * [AJUSTAR] Nomes dos campos do payload conforme documentação pro2sms.
 * Segurança: token partilhado em query + rota fora do CSRF (api/*) +
 * atualiza APENAS o status de logs_sms — superfície mínima.
 */
class SmsCallbackController extends BaseController
{
    public function receber()
    {
        if (! hash_equals((string) env('pro2sms.callbackToken'), (string) $this->request->getGet('token'))) {
            return $this->response->setStatusCode(403);
        }

        $payload   = $this->request->getJSON(true) ?? $this->request->getPost();
        $messageId = $payload['message_id'] ?? $payload['id'] ?? null;      // [AJUSTAR]
        $estado    = strtolower((string) ($payload['status'] ?? ''));       // [AJUSTAR]

        if ($messageId === null) {
            return $this->response->setStatusCode(422)->setJSON(['erro' => 'message_id em falta']);
        }

        $novoStatus = match ($estado) {
            'delivered', 'entregue'          => 'entregue',
            'failed', 'undelivered', 'falha' => 'falhado',
            'expired', 'expirado'            => 'expirado',
            default                          => null,
        };

        if ($novoStatus !== null) {
            db_connect()->table('logs_sms')
                ->where('provider_message_id', $messageId)
                ->update(['status' => $novoStatus, 'updated_at' => utc_agora()]);
        }

        return $this->response->setJSON(['ok' => true]);
    }
}
