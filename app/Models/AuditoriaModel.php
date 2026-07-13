<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model da tabela `auditoria_logs`.
 *
 * Campos derivados DIRETAMENTE do esquema v2.0 (app/Database/sql/schema_v2.sql).
 * Regras de negócio ficam nos services, nunca aqui.
 */
class AuditoriaModel extends Model
{
    use \App\Traits\Auditavel;

    protected $table         = 'auditoria_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'user_id',
        'acao',
        'entidade',
        'entidade_id',
        'descricao',
        'dados_antes',
        'dados_depois',
        'ip_address',
        'user_agent',
    ];

    protected $beforeUpdate = ['auditavelCapturarAntes'];
    protected $afterInsert  = ['auditavelAposInserir'];
    protected $afterUpdate  = ['auditavelAposAtualizar'];
    protected $afterDelete  = ['auditavelAposEliminar'];
}
