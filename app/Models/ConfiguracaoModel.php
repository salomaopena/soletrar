<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model da tabela `configuracoes`.
 *
 * ATENÇÃO: esta tabela NÃO tem coluna `id` — a chave primária é `chave`
 * (VARCHAR). Daí primaryKey='chave' e $useAutoIncrement=false.
 */
class ConfiguracaoModel extends Model
{
    protected $table = 'configuracoes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'object';
    protected $useTimestamps = false;   // só tem updated_at
    protected $protectFields = false;
}
