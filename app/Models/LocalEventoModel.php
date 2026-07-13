<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model da tabela `locais_evento`.
 *
 * Campos derivados DIRETAMENTE do esquema v2.0 (app/Database/sql/schema_v2.sql).
 * Regras de negócio ficam nos services, nunca aqui.
 */
class LocalEventoModel extends Model
{
    use \App\Traits\Auditavel;

    protected $table = 'locais_evento';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $protectFields = false;

    protected $beforeUpdate = ['auditavelCapturarAntes'];
    protected $afterInsert = ['auditavelAposInserir'];
    protected $afterUpdate = ['auditavelAposAtualizar'];
    protected $afterDelete = ['auditavelAposEliminar'];
}
