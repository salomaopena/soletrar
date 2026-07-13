<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * Trait para Models: regista automaticamente inserções, alterações
 * e eliminações em auditoria_logs, com snapshot antes/depois.
 *
 * COMO USAR no Model:
 *
 *   class InscricaoModel extends Model
 *   {
 *       use \App\Traits\Auditavel;
 *
 *       // Regista os callbacks do trait:
 *       protected $beforeUpdate = ['auditavelCapturarAntes'];
 *       protected $afterInsert  = ['auditavelAposInserir'];
 *       protected $afterUpdate  = ['auditavelAposAtualizar'];
 *       protected $afterDelete  = ['auditavelAposEliminar'];
 *   }
 */
trait Auditavel
{
    /** Snapshot dos registos antes do UPDATE, indexado por ID. */
    private array $auditavelAntes = [];

    protected function auditavelCapturarAntes(array $dados): array
    {
        foreach ((array) ($dados['id'] ?? []) as $id) {
            $linha = $this->builder()->where($this->primaryKey, $id)->get()->getRowArray();
            if ($linha !== null) {
                $this->auditavelAntes[$id] = $linha;
            }
        }

        return $dados;
    }

    protected function auditavelAposInserir(array $dados): array
    {
        if ($dados['result'] ?? false) {
            service('auditoria')->registar(
                acao: 'criar',
                entidade: $this->table,
                entidadeId: $dados['id'] ?? null,
                dadosDepois: (array) $dados['data'],
            );
        }

        return $dados;
    }

    protected function auditavelAposAtualizar(array $dados): array
    {
        if (! ($dados['result'] ?? false)) {
            return $dados;
        }

        foreach ((array) ($dados['id'] ?? []) as $id) {
            service('auditoria')->registar(
                acao: 'editar',
                entidade: $this->table,
                entidadeId: $id,
                dadosAntes: $this->auditavelAntes[$id] ?? null,
                dadosDepois: (array) $dados['data'],
            );
            unset($this->auditavelAntes[$id]);
        }

        return $dados;
    }

    protected function auditavelAposEliminar(array $dados): array
    {
        if ($dados['result'] ?? false) {
            foreach ((array) ($dados['id'] ?? []) as $id) {
                service('auditoria')->registar(
                    acao: $dados['purge'] ?? false ? 'eliminar_definitivo' : 'eliminar',
                    entidade: $this->table,
                    entidadeId: $id,
                );
            }
        }

        return $dados;
    }
}
