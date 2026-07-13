<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Palavras;

use App\Controllers\Admin\CrudBaseController;

class CategoriasController extends CrudBaseController
{
    protected function model(): string  { return 'PalavraCategoriaModel'; }
    protected function rota(): string   { return 'admin/palavras/categorias'; }
    protected function titulo(): string { return 'Categorias de palavras'; }
    protected function ordenarPor(): string { return 'nome'; }

    protected function colunas(): array
    {
        return ['nome' => 'Categoria', 'descricao' => 'Descrição'];
    }

    protected function campos(): array
    {
        return [
            ['nome' => 'nome', 'rotulo' => 'Nome', 'obrigatorio' => true, 'largura' => 6],
            ['nome' => 'descricao', 'rotulo' => 'Descrição', 'tipo' => 'textarea', 'largura' => 12],
        ];
    }

    protected function regras(): array
    {
        return ['nome' => 'required|min_length[2]'];
    }
}
