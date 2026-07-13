<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Concurso;

use App\Controllers\Admin\CrudBaseController;

/** Categorias por classe/idade de cada edição (RN-03). */
class CategoriasController extends CrudBaseController
{
    protected function model(): string  { return 'CategoriaModel'; }
    protected function rota(): string   { return 'admin/categorias'; }
    protected function titulo(): string { return 'Categorias da competição'; }
    protected function ordenarPor(): string { return 'ordem'; }

    protected function colunas(): array
    {
        return [
            'nome'          => 'Categoria',
            'classe_minima' => 'Classe mín.',
            'classe_maxima' => 'Classe máx.',
            'idade_minima'  => 'Idade mín.',
            'idade_maxima'  => 'Idade máx.',
        ];
    }

    protected function campos(): array
    {
        return [
            ['nome' => 'edicao_id', 'rotulo' => 'Edição', 'tipo' => 'select', 'obrigatorio' => true,
             'largura' => 6, 'opcoes' => $this->opcoes('EdicaoModel')],
            ['nome' => 'nome', 'rotulo' => 'Nome da categoria', 'obrigatorio' => true, 'largura' => 6],
            ['nome' => 'classe_minima', 'rotulo' => 'Classe mínima', 'tipo' => 'number', 'obrigatorio' => true, 'largura' => 3],
            ['nome' => 'classe_maxima', 'rotulo' => 'Classe máxima', 'tipo' => 'number', 'obrigatorio' => true, 'largura' => 3],
            ['nome' => 'idade_minima', 'rotulo' => 'Idade mínima', 'tipo' => 'number', 'largura' => 3],
            ['nome' => 'idade_maxima', 'rotulo' => 'Idade máxima', 'tipo' => 'number', 'largura' => 3],
            ['nome' => 'ordem', 'rotulo' => 'Ordem', 'tipo' => 'number', 'largura' => 3],
            ['nome' => 'descricao', 'rotulo' => 'Descrição', 'tipo' => 'textarea', 'largura' => 12],
        ];
    }

    protected function regras(): array
    {
        return [
            'edicao_id'     => 'required|is_natural_no_zero',
            'nome'          => 'required|min_length[3]',
            'classe_minima' => 'required|classe_valida',
            'classe_maxima' => 'required|classe_valida',
        ];
    }
}
