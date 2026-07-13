<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Sistema;

use App\Controllers\Admin\AdminBaseController;

/** Configurações globais (tabela `configuracoes`). */
class ConfiguracoesController extends AdminBaseController
{
    
    public function index()
    {
        return view('admin/crud/index', [
            'titulo'    => 'Configurações',
            'rotaBase'  => 'admin/sistema/configuracoes',
            'podeCriar' => false,
            //'podeEditar' => false,   // a tabela `configuracoes` não tem `id`
            //'campoId'    => 'chave', 
            'colunas'   => [
                'chave'     => 'Chave',
                'valor'     => 'Valor',
                'grupo'     => 'Grupo',
                'descricao' => 'Descrição',
            ],
            'registos'  => model('ConfiguracaoModel')->orderBy('grupo')->orderBy('chave')->findAll() ?? [],
            'vazio'     => 'Sem configurações.',
        ]);
    }
}
