<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Geografia;

use App\Controllers\Admin\CrudBaseController;

class MunicipiosController extends CrudBaseController
{
    protected function model(): string  { return 'MunicipioModel'; }
    protected function rota(): string   { return 'admin/geografia/municipios'; }
    protected function titulo(): string { return 'Municípios'; }
    protected function ordenarPor(): string { return 'nome'; }

    protected function colunas(): array
    {
        return [
            'nome'  => 'Município',
            'ativo' => ['rotulo' => 'Ativo', 'tipo' => 'bool'],
        ];
    }

    protected function campos(): array
    {
        return [
            ['nome' => 'nome', 'rotulo' => 'Nome do município', 'obrigatorio' => true, 'largura' => 8],
            ['nome' => 'provincia_id', 'rotulo' => 'Província', 'tipo' => 'select',
             'obrigatorio' => true, 'largura' => 4, 'opcoes' => $this->opcoes('ProvinciaModel')],
            ['nome' => 'ativo', 'rotulo' => 'Ativo', 'tipo' => 'checkbox'],
        ];
    }

    protected function regras(): array
    {
        return [
            'nome'         => 'required|min_length[2]|max_length[100]',
            'provincia_id' => 'required|is_natural_no_zero',
        ];
    }
}
