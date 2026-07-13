<?php

declare(strict_types=1);

/**
 * Helper de formatação para o contexto angolano.
 */

if (! function_exists('moeda_aoa')) {
    /** Formata um valor em Kwanzas (ex.: 1 500,00 Kz). */
    function moeda_aoa(float|int|null $valor): string
    {
        return number_format((float) ($valor ?? 0), 2, ',', ' ') . ' Kz';
    }
}

if (! function_exists('telefone_formatar')) {
    /** Apresenta um telefone angolano legível: +244 923 123 456. */
    function telefone_formatar(?string $telefone): string
    {
        if ($telefone === null) {
            return '—';
        }
        $d = preg_replace('/\D/', '', $telefone);
        if (preg_match('/^244(\d{3})(\d{3})(\d{3})$/', $d, $m)) {
            return "+244 {$m[1]} {$m[2]} {$m[3]}";
        }
        if (preg_match('/^(\d{3})(\d{3})(\d{3})$/', $d, $m)) {
            return "{$m[1]} {$m[2]} {$m[3]}";
        }
        return $telefone;
    }
}

if (! function_exists('classe_ordinal')) {
    /** 7 → "7.ª classe". */
    function classe_ordinal(int $classe): string
    {
        return $classe . '.ª classe';
    }
}
