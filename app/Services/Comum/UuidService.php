<?php

declare(strict_types=1);

namespace App\Services\Comum;

/**
 * Geração de UUID v4 (identificadores públicos de candidatos e
 * inscrições — v2.0 do banco). Sem dependência externa: random_bytes
 * com os bits de versão/variante corretos (RFC 4122).
 */
final class UuidService
{
    public function v4(): string
    {
        $bytes = random_bytes(16);

        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40); // versão 4
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80); // variante RFC

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
