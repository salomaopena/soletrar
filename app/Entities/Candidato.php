<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use CodeIgniter\I18n\Time;

/**
 * Entidade Candidato — aluno concorrente (até à 8.ª classe).
 */
class Candidato extends Entity
{
    protected $casts = [
        'id'                          => 'integer',
        'classe_atual'                => 'integer',
        'tem_necessidades_especiais'  => 'boolean',
        'data_nascimento'             => 'datetime',
    ];

    /**
     * Idade do candidato numa data de referência.
     * A elegibilidade por idade é SEMPRE calculada à data de referência
     * da edição (não à data de hoje), para que a validação seja estável
     * durante todo o período de inscrições.
     */
    public function idadeEm(Time $referencia): int
    {
        return $this->data_nascimento->difference($referencia)->getYears();
    }

    /** Nome para palco/listagens: nome preferido, senão primeiro + último. */
    public function nomeExibicao(): string
    {
        if (! empty($this->attributes['nome_preferido'])) {
            return $this->attributes['nome_preferido'];
        }

        $partes = preg_split('/\s+/', trim($this->attributes['nome_completo']));

        return count($partes) > 1
            ? $partes[0] . ' ' . end($partes)
            : $partes[0];
    }
}
