<?php

declare(strict_types=1);

namespace App\Services\Notificacoes\Canais;

use App\Libraries\Sms\SmsMensagem;
use App\Libraries\Sms\SmsProviderInterface;
use CodeIgniter\Database\ConnectionInterface;
use Throwable;

/**
 * Canal de SMS: depende SÓ da interface do provedor (Fase 3: provedor
 * substituível numa linha do Services.php) + log obrigatório em logs_sms.
 */
final class CanalSms implements CanalInterface
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly SmsProviderInterface $provider,
    ) {
    }

    public function enviar(array $item): bool
    {
        try {
            $mensagem = new SmsMensagem($item['destinatario'], $item['corpo']);
        } catch (Throwable $e) {
            // Telefone inválido é falha DEFINITIVA — retry não resolve.
            $this->registarLog($item, null, 1, 'falhado', null, [], $e->getMessage());

            // Devolvemos true para a fila NÃO reagendar; o log fica 'falhado'.
            log_message('warning', 'SMS descartado (telefone inválido): {dest}', [
                'dest' => $item['destinatario'],
            ]);

            return true;
        }

        $resultado = $this->provider->enviar($mensagem);

        $this->registarLog(
            $item,
            $resultado->providerMessageId,
            $mensagem->partes,
            $resultado->sucesso ? 'enviado' : 'falhado',
            $resultado->custo,
            $resultado->respostaBruta,
            $resultado->erro,
            $mensagem->telefone,
        );

        return $resultado->sucesso;
    }

    private function registarLog(
        array $item,
        ?string $providerMessageId,
        int $partes,
        string $status,
        ?float $custo,
        array $respostaBruta,
        ?string $erro,
        ?string $telefoneNormalizado = null,
    ): void {
        $this->db->table('logs_sms')->insert([
            'fila_id'             => $item['id'] ?? null,
            'user_id'             => $item['user_id'] ?? null,
            'telefone'            => $telefoneNormalizado ?? $item['destinatario'],
            'mensagem'            => mb_substr($item['corpo'], 0, 500),
            'partes'              => $partes,
            'provider'            => $this->provider->nome(),
            'provider_message_id' => $providerMessageId,
            'status'              => $status,
            'custo'               => $custo,
            'resposta_api'        => json_encode($respostaBruta, JSON_UNESCAPED_UNICODE),
            'erro'                => $erro,
            'created_at'          => utc_agora(),
        ]);
    }
}
