<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Regras de validação específicas do contexto angolano/educacional.
 *
 * Registar em app/Config/Validation.php:
 *   public array $ruleSets = [ ..., \App\Validation\RegrasAngola::class ];
 *
 * Mensagens em app/Language/pt-AO/Validation.php.
 */
class RegrasAngola
{
    /**
     * Telefone móvel angolano: 9 dígitos começando por 9,
     * com prefixo +244 opcional. Ex.: 923123456, +244923123456.
     */
    public function telefone_ao(?string $valor): bool
    {
        if ($valor === null || $valor === '') {
            return false;
        }

        return preg_match('/^(\+244)?9\d{8}$/', preg_replace('/[\s\-]/', '', $valor)) === 1;
    }

    /**
     * Bilhete de Identidade angolano: 9 dígitos + 2 letras + 3 dígitos.
     * Ex.: 001234567LA041.
     */
    public function bi_angola(?string $valor): bool
    {
        if ($valor === null || $valor === '') {
            return false;
        }

        return preg_match('/^\d{9}[A-Z]{2}\d{3}$/', strtoupper(trim($valor))) === 1;
    }

    /** Classe escolar válida para o concurso (1.ª a 8.ª). */
    public function classe_valida(?string $valor): bool
    {
        return is_numeric($valor) && (int) $valor >= 1 && (int) $valor <= 8;
    }

    /** Data no passado ou hoje (ex.: data de nascimento). */
    public function data_nao_futura(?string $valor): bool
    {
        if ($valor === null || strtotime($valor) === false) {
            return false;
        }

        return strtotime($valor) <= time();
    }

    /**
     * Idade dentro do intervalo [min,max] à data de hoje.
     * Uso: 'data_nascimento' => 'idade_entre[6,17]'
     */
    public function idade_entre(?string $valor, string $params): bool
    {
        if ($valor === null || strtotime($valor) === false) {
            return false;
        }

        [$min, $max] = array_map('intval', explode(',', $params));
        $idade = (new \DateTime($valor))->diff(new \DateTime('today'))->y;

        return $idade >= $min && $idade <= $max;
    }
}
