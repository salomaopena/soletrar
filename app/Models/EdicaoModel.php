<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model da tabela `edicoes_concurso`.
 * Campos derivados do esquema v2.0.
 */
class EdicaoModel extends Model
{
    use \App\Traits\Auditavel;

    protected $table         = 'edicoes_concurso';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;
    protected $protectFields = false;

    protected $beforeUpdate = ['auditavelCapturarAntes'];
    protected $afterInsert  = ['auditavelAposInserir'];
    protected $afterUpdate  = ['auditavelAposAtualizar'];
    protected $afterDelete  = ['auditavelAposEliminar'];

    /**
     * Edição atualmente ABERTA para inscrições (status + dentro do prazo).
     * Usada pelo InscricaoController público.
     */
    public function edicaoAtivaParaInscricao(): ?object
    {
        $agora = utc_agora();

        return $this->where('status', 'inscricoes_abertas')
            ->where('data_abertura_inscricoes <=', $agora)
            ->where('data_encerramento_inscricoes >=', $agora)
            ->orderBy('ano', 'DESC')
            ->first();
    }
}
