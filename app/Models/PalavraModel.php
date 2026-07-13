<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model da tabela `palavras`.
 *
 * Campos derivados DIRETAMENTE do esquema v2.0 (app/Database/sql/schema_v2.sql).
 * Regras de negócio ficam nos services, nunca aqui.
 */
class PalavraModel extends Model
{
    use \App\Traits\Auditavel;

    protected $table         = 'palavras';
    protected $primaryKey    = 'id';
    protected $returnType    = \App\Entities\Palavra::class;
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    protected $protectFields = false;

    protected $beforeUpdate = ['auditavelCapturarAntes'];
    protected $afterInsert  = ['auditavelAposInserir'];
    protected $afterUpdate  = ['auditavelAposAtualizar'];
    protected $afterDelete  = ['auditavelAposEliminar'];
}
