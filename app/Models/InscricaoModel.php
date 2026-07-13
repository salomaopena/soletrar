<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Comum\Escopo;
use CodeIgniter\Model;

/**
 * Model de inscrições: persistência e escopos de consulta.
 * As regras de negócio vivem no InscricaoService.
 */
class InscricaoModel extends Model
{
    use \App\Traits\Auditavel;

    protected $table         = 'inscricoes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;

    protected $protectFields = false;

    protected $beforeUpdate = ['auditavelCapturarAntes'];
    protected $afterInsert  = ['auditavelAposInserir'];
    protected $afterUpdate  = ['auditavelAposAtualizar'];
    protected $afterDelete  = ['auditavelAposEliminar'];

    /** Filtro territorial obrigatório em TODAS as listagens administrativas. */
    public function noEscopo(Escopo $escopo): static
    {
        if ($escopo->eNacional()) {
            return $this;
        }

        return match ($escopo->nivel) {
            'provincial', 'municipal' => $this->whereIn('inscricoes.provincia_id', $escopo->provincias ?: [0]),
            'escolar'                 => $this->whereIn('inscricoes.escola_id', $escopo->escolas ?: [0]),
            default                   => $this->where('1 = 0'), // escopo desconhecido → nada
        };
    }

    /** Join de listagem. */
    public function comCandidatoEEscola(): static
    {
        return $this
            ->select('inscricoes.*, c.numero_inscricao, c.nome_completo, c.classe_atual,
                      e.nome AS escola, p.nome AS provincia')
            ->join('candidatos c', 'c.id = inscricoes.candidato_id')
            ->join('escolas e', 'e.id = inscricoes.escola_id')
            ->join('provincias p', 'p.id = inscricoes.provincia_id');
    }

    /** Join de detalhe (inclui categoria e edição). */
    public function comDetalhes(): static
    {
        return $this->comCandidatoEEscola()
            ->select('cat.nome AS categoria, ed.nome AS edicao, ed.ano AS edicao_ano')
            ->join('categorias_competicao cat', 'cat.id = inscricoes.categoria_id')
            ->join('edicoes_concurso ed', 'ed.id = inscricoes.edicao_id');
    }
}
