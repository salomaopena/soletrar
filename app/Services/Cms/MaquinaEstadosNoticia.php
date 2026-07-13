<?php

declare(strict_types=1);

namespace App\Services\Cms;

use RuntimeException;

/**
 * Máquina de estados do fluxo editorial (inspirada no WordPress).
 *
 *   rascunho ──► revisao ──► publicada ◄── agendada
 *      ▲            │            │
 *      └────────────┘            ▼
 *   (devolver)               arquivada
 *   qualquer estado ──► lixeira ──► rascunho (restaurar)
 *
 * Cada transição declara a PERMISSÃO Shield necessária, pelo que a
 * autorização editorial vive aqui — não espalhada pelos controllers.
 */
final class MaquinaEstadosNoticia
{
    /**
     * [estadoOrigem => [transicao => [destino, permissão]]]
     */
    private const TRANSICOES = [
        'rascunho' => [
            'submeter'  => ['revisao',   'cms.conteudo.criar'],
            'publicar'  => ['publicada', 'cms.conteudo.publicar'], // atalho p/ editores
            'agendar'   => ['agendada',  'cms.conteudo.publicar'],
            'eliminar'  => ['lixeira',   'cms.conteudo.criar'],
        ],
        'revisao' => [
            'devolver'  => ['rascunho',  'cms.conteudo.publicar'],
            'publicar'  => ['publicada', 'cms.conteudo.publicar'],
            'agendar'   => ['agendada',  'cms.conteudo.publicar'],
            'eliminar'  => ['lixeira',   'cms.conteudo.publicar'],
        ],
        'agendada' => [
            'publicar'  => ['publicada', 'cms.conteudo.publicar'], // manual ou cron
            'devolver'  => ['rascunho',  'cms.conteudo.publicar'],
            'eliminar'  => ['lixeira',   'cms.conteudo.publicar'],
        ],
        'publicada' => [
            'arquivar'  => ['arquivada', 'cms.conteudo.publicar'],
            'devolver'  => ['rascunho',  'cms.conteudo.publicar'],
            'eliminar'  => ['lixeira',   'cms.conteudo.publicar'],
        ],
        'arquivada' => [
            'publicar'  => ['publicada', 'cms.conteudo.publicar'],
            'eliminar'  => ['lixeira',   'cms.conteudo.publicar'],
        ],
        'lixeira' => [
            'restaurar' => ['rascunho',  'cms.conteudo.publicar'],
        ],
    ];

    /**
     * Valida e resolve uma transição.
     *
     * @return string O estado de destino
     * @throws RuntimeException Se a transição não existir ou faltar permissão
     */
    public function transitar(string $estadoAtual, string $transicao): string
    {
        $regra = self::TRANSICOES[$estadoAtual][$transicao]
            ?? throw new RuntimeException(
                lang('Cms.transicaoInvalida', [$transicao, $estadoAtual])
            );

        [$destino, $permissao] = $regra;

        if (! auth()->user()?->can($permissao)) {
            throw new RuntimeException(lang('Cms.semPermissaoEditorial'));
        }

        return $destino;
    }

    /** Transições disponíveis a partir de um estado, filtradas por permissão. */
    public function disponiveis(string $estadoAtual): array
    {
        $resultado = [];

        foreach (self::TRANSICOES[$estadoAtual] ?? [] as $transicao => [$destino, $permissao]) {
            if (auth()->user()?->can($permissao)) {
                $resultado[$transicao] = $destino;
            }
        }

        return $resultado;
    }
}
