<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Concurso;

use App\Controllers\Admin\CrudBaseController;

class LocaisController extends CrudBaseController
{
    protected function model(): string  { return 'LocalEventoModel'; }
    protected function rota(): string   { return 'admin/locais'; }
    protected function titulo(): string { return 'Locais de evento'; }
    protected function ordenarPor(): string { return 'nome'; }

    protected function colunas(): array
    {
        return ['nome' => 'Local', 'endereco' => 'Endereço', 'capacidade' => 'Capacidade'];
    }

    protected function campos(): array
    {
        return [
            ['nome' => 'nome', 'rotulo' => 'Nome do local', 'obrigatorio' => true, 'largura' => 8],
            ['nome' => 'capacidade', 'rotulo' => 'Capacidade', 'tipo' => 'number', 'largura' => 4],
            ['nome' => 'provincia_id', 'rotulo' => 'Província', 'tipo' => 'select', 'largura' => 6,
             'opcoes' => $this->opcoes('ProvinciaModel')],
            ['nome' => 'municipio_id', 'rotulo' => 'Município', 'tipo' => 'select', 'largura' => 6,
             'opcoes' => $this->opcoes('MunicipioModel')],
            ['nome' => 'endereco', 'rotulo' => 'Endereço', 'largura' => 12],
            ['nome' => 'contacto', 'rotulo' => 'Contacto', 'largura' => 6],
        ];
    }

    protected function regras(): array
    {
        return ['nome' => 'required|min_length[3]'];
    }
}
