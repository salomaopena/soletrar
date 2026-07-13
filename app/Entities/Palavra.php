<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * Entidade Palavra do banco de palavras.
 */
class Palavra extends Entity
{
    protected $casts = [
        'id'                  => 'integer',
        'validada'            => 'boolean',
        'usada_em_concursos'  => 'integer',
        'nivel_minimo_classe' => 'integer',
        'nivel_maximo_classe' => 'integer',
    ];

    /** Rótulo legível da dificuldade. */
    public function dificuldadeRotulo(): string
    {
        return lang('Concurso.dificuldade_' . $this->attributes['dificuldade']);
    }

    /** Silabação para exibição (ex.: pa-ra-le-le-pí-pe-do). */
    public function silabacaoExibicao(): string
    {
        return $this->attributes['silabacao'] ?: $this->attributes['palavra'];
    }
}
