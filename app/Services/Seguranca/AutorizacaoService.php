<?php

declare(strict_types=1);

namespace App\Services\Seguranca;

use App\Exceptions\AutorizacaoException;
use App\Services\Comum\Escopo;

/**
 * Segunda camada de autorização (a primeira é o Shield):
 * verifica se um REGISTO concreto está dentro do escopo territorial
 * do utilizador. Chamado pelos services/controllers ANTES de qualquer
 * leitura de detalhe ou escrita.
 */
final class AutorizacaoService
{
    /**
     * Exige que o registo pertença ao escopo. O registo pode ser uma
     * Entity ou um array associativo — só precisa de expor provincia_id
     * (e opcionalmente escola_id).
     *
     * @throws AutorizacaoException
     */
    public function exigirEscopo(Escopo $escopo, object|array $registo): void
    {
        $provinciaId = $this->extrair($registo, 'provincia_id');
        $escolaId    = $this->extrair($registo, 'escola_id');

        $dentro = match ($escopo->nivel) {
            'nacional'               => true,
            'provincial', 'municipal' => $escopo->abrangeProvincia($provinciaId),
            'escolar'                => $escopo->abrangeEscola($escolaId),
            default                  => false,
        };

        if (! $dentro) {
            // Tentativa de acesso fora do escopo é sempre auditada.
            service('auditoria')->registar(
                acao: 'acesso_negado_escopo',
                entidade: is_object($registo) ? $registo::class : 'array',
                entidadeId: $this->extrair($registo, 'id'),
                descricao: sprintf(
                    'Escopo %s tentou aceder a registo da província %s',
                    $escopo->nivel,
                    $provinciaId ?? '?'
                ),
            );

            throw new AutorizacaoException(lang('Geral.foraDoEscopo'));
        }
    }

    private function extrair(object|array $registo, string $campo): ?int
    {
        $valor = is_array($registo) ? ($registo[$campo] ?? null) : ($registo->{$campo} ?? null);

        return $valor === null ? null : (int) $valor;
    }
}
