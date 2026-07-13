<?php

declare(strict_types=1);

namespace App\Libraries\Sms\Providers;

use App\Libraries\Sms\SmsMensagem;
use App\Libraries\Sms\SmsProviderInterface;
use App\Libraries\Sms\SmsResultado;
use CodeIgniter\HTTP\CURLRequest;
use Config\Pro2Sms as Pro2SmsConfig;
use Throwable;

/**
 * Provedor de SMS via API https://pro2sms.ao
 *
 * Estrutura completa de integração; os pontos marcados com [AJUSTAR]
 * (endpoint, nomes de campos do payload e da resposta) são os únicos
 * a afinar com a documentação oficial e as credenciais reais.
 *
 * Garantias:
 *  - nunca lança por falha de rede: devolve SmsResultado::falha();
 *  - toda a chamada é registada no log da aplicação;
 *  - a resposta bruta segue no resultado para gravação em logs_sms
 *    (diagnóstico sem depender do painel do provedor).
 */
final class Pro2SmsProvider implements SmsProviderInterface
{
    public function __construct(
        private readonly Pro2SmsConfig $config,
        private readonly CURLRequest $http,
    ) {
    }

    public function nome(): string
    {
        return 'pro2sms';
    }

    public function enviar(SmsMensagem $mensagem): SmsResultado
    {
        // [AJUSTAR] Estrutura do payload conforme documentação pro2sms.
        $payload = [
            'to'      => $mensagem->telefone,        // E.164 já normalizado
            'from'    => $this->config->senderId,
            'message' => $mensagem->texto,
        ];

        try {
            $resposta = $this->http->request(
                'POST',
                rtrim($this->config->baseUrl, '/') . $this->config->endpointEnvio,
                [
                    'headers' => [
                        // [AJUSTAR] Esquema de autenticação (Bearer vs api-key em header/query).
                        'Authorization' => 'Bearer ' . $this->config->apiKey,
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                    ],
                    'json'        => $payload,
                    'timeout'     => $this->config->timeout,
                    'http_errors' => false,   // 4xx/5xx tratados abaixo, não como exceção
                ]
            );
        } catch (Throwable $e) {
            // Rede em baixo, DNS, timeout... → falha recuperável (a fila fará retry).
            log_message('error', 'pro2sms: falha de comunicação: {erro}', ['erro' => $e->getMessage()]);

            return SmsResultado::falha('Falha de comunicação: ' . $e->getMessage());
        }

        $codigo = $resposta->getStatusCode();
        $corpo  = json_decode($resposta->getBody(), true) ?? ['raw' => (string) $resposta->getBody()];

        if ($codigo >= 200 && $codigo < 300) {
            log_message('info', 'pro2sms: SMS aceite para {tel} ({partes} parte(s))', [
                'tel' => $mensagem->telefone, 'partes' => $mensagem->partes,
            ]);

            return SmsResultado::ok(
                // [AJUSTAR] Campos da resposta de sucesso.
                providerMessageId: $corpo['message_id'] ?? $corpo['id'] ?? null,
                custo: isset($corpo['cost']) ? (float) $corpo['cost'] : null,
                respostaBruta: $corpo,
            );
        }

        $erro = $corpo['error'] ?? $corpo['message'] ?? "HTTP {$codigo}";
        log_message('error', 'pro2sms: envio recusado ({codigo}): {erro}', [
            'codigo' => $codigo, 'erro' => is_string($erro) ? $erro : json_encode($erro),
        ]);

        return SmsResultado::falha(is_string($erro) ? $erro : json_encode($erro), $corpo);
    }
}
