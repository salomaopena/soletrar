<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model da tabela `candidatos`.
 *
 * Campos derivados DIRETAMENTE do esquema v2.0 (app/Database/sql/schema_v2.sql).
 * Regras de negócio ficam nos services, nunca aqui.
 */
class CandidatoModel extends Model
{
    use \App\Traits\Auditavel;

    protected $table         = 'candidatos';
    protected $primaryKey    = 'id';
    protected $returnType    = \App\Entities\Candidato::class;
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    protected $protectFields = false;

    protected $beforeUpdate = ['auditavelCapturarAntes'];
    protected $afterInsert  = ['auditavelAposInserir'];
    protected $afterUpdate  = ['auditavelAposAtualizar'];
    protected $afterDelete  = ['auditavelAposEliminar'];
}
