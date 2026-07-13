<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Cms;

use App\Controllers\Admin\CrudBaseController;

class CategoriasController extends CrudBaseController
{
    protected function model(): string  { return 'NoticiaCategoriaModel'; }
    protected function rota(): string   { return 'admin/cms/categorias'; }
    protected function titulo(): string { return 'Categorias de notícias'; }
    protected function ordenarPor(): string { return 'ordem'; }

    protected function colunas(): array
    {
        return ['nome' => 'Categoria', 'slug' => 'Slug', 'ordem' => 'Ordem'];
    }

    protected function campos(): array
    {
        return [
            ['nome' => 'nome', 'rotulo' => 'Nome', 'obrigatorio' => true, 'largura' => 6],
            ['nome' => 'cor', 'rotulo' => 'Cor (hex)', 'largura' => 3, 'ajuda' => 'Ex.: #2AA8A3'],
            ['nome' => 'ordem', 'rotulo' => 'Ordem', 'tipo' => 'number', 'largura' => 3],
            ['nome' => 'descricao', 'rotulo' => 'Descrição', 'tipo' => 'textarea', 'largura' => 12],
        ];
    }

    protected function regras(): array
    {
        return ['nome' => 'required|min_length[2]'];
    }

    /** O slug é derivado do nome (transliteração pt). */
    protected function prepararDados(array $dados): array
    {
        helper('texto');
        $dados['slug'] = slug_pt($dados['nome']);

        return $dados;
    }
}
