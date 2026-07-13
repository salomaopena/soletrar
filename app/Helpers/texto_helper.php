<?php

declare(strict_types=1);

/**
 * Helper de texto: slug em português, excertos e utilidades editoriais.
 */

if (! function_exists('slug_pt')) {
    /**
     * Slug com transliteração completa do português (motivo pelo qual o
     * trigger SQL foi removido na Fase 2: á à â ã é ê í ó ô õ ú ü ç...).
     */
    function slug_pt(string $texto): string
    {
        $mapa = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];

        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = strtr($texto, $mapa);

        // Restos não-ASCII (aspas curvas, travessões...) → transliterar/remover.
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;

        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);

        return trim(preg_replace('/-{2,}/', '-', $texto), '-');
    }
}

if (! function_exists('excerto')) {
    /** Excerto de texto sem HTML, cortado em limite de palavra. */
    function excerto(string $html, int $maxCaracteres = 180): string
    {
        $texto = trim(preg_replace('/\s+/', ' ', strip_tags($html)));

        if (mb_strlen($texto) <= $maxCaracteres) {
            return $texto;
        }

        $corte = mb_substr($texto, 0, $maxCaracteres);
        $ultimoEspaco = mb_strrpos($corte, ' ');

        return ($ultimoEspaco ? mb_substr($corte, 0, $ultimoEspaco) : $corte) . '…';
    }
}
