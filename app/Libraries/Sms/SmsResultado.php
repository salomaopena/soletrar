<?php

declare(strict_types=1);

namespace App\Libraries\Sms;

/**
 * Resultado normalizado do envio — o CanalSms só conhece esta forma,
 * seja qual for o provedor.
 */
final class SmsResultado
{
    private function __construct(
        public readonly bool $sucesso,
        public readonly ?string $providerMessageId,
        public readonly ?float $custo,
        public readonly ?string $erro,
        public readonly array $respostaBruta,
    ) {
    }

    public static function ok(?string $messageId, ?float $custo, array $respostaBruta): self
    {
        return new self(true, $messageId, $custo, null, $respostaBruta);
    }

    public static function falha(string $erro, array $respostaBruta = []): self
    {
        return new self(false, null, null, $erro, $respostaBruta);
    }
}
