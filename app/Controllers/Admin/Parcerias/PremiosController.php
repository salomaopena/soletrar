<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Parcerias;

use App\Controllers\Admin\CrudBaseController;

class PremiosController extends CrudBaseController
{
    protected function model(): string  { return 'PremioModel'; }
    protected function rota(): string   { return 'admin/parcerias/premios'; }
    protected function titulo(): string { return 'Prémios'; }
    protected function ordenarPor(): string { return 'posicao'; }

    protected function colunas(): array
    {
        return [
            'nome'            => 'Prémio',
            'posicao'         => 'Posição',
            'tipo'            => 'Tipo',
            'valor_monetario' => 'Valor',
        ];
    }

    protected function campos(): array
    {
        return [
            ['nome' => 'edicao_id', 'rotulo' => 'Edição', 'tipo' => 'select', 'obrigatorio' => true,
             'largura' => 6, 'opcoes' => $this->opcoes('EdicaoModel')],
            ['nome' => 'nome', 'rotulo' => 'Nome do prémio', 'obrigatorio' => true, 'largura' => 6],
            ['nome' => 'posicao', 'rotulo' => 'Posição premiada', 'tipo' => 'number', 'largura' => 3,
             'ajuda' => '1 = vencedor'],
            ['nome' => 'tipo', 'rotulo' => 'Tipo', 'tipo' => 'select', 'largura' => 3,
             'opcoes' => [
                 'monetario'     => 'Monetário',
                 'bolsa_estudo'  => 'Bolsa de estudo',
                 'material'      => 'Material',
                 'troféu'        => 'Troféu',
                 'medalha'       => 'Medalha',
                 'certificado'   => 'Certificado',
                 'outro'         => 'Outro',
             ]],
            ['nome' => 'valor_monetario', 'rotulo' => 'Valor', 'tipo' => 'number', 'largura' => 2],
            ['nome' => 'moeda', 'rotulo' => 'Moeda', 'tipo' => 'select', 'largura' => 2,
             'opcoes' => ['AOA' => 'AOA (Kz)', 'USD' => 'USD', 'EUR' => 'EUR']],
            ['nome' => 'categoria_id', 'rotulo' => 'Categoria (opcional)', 'tipo' => 'select',
             'largura' => 3, 'opcoes' => $this->opcoes('CategoriaModel')],
            ['nome' => 'fase_id', 'rotulo' => 'Fase (opcional)', 'tipo' => 'select',
             'largura' => 3, 'opcoes' => $this->opcoes('FaseModel')],
            ['nome' => 'patrocinador_id', 'rotulo' => 'Patrocinador', 'tipo' => 'select', 'largura' => 3,
             'opcoes' => $this->opcoes('PatrocinadorModel')],
            ['nome' => 'descricao', 'rotulo' => 'Descrição', 'tipo' => 'textarea', 'largura' => 12],
        ];
    }

    protected function regras(): array
    {
        return [
            'edicao_id' => 'required|is_natural_no_zero',
            'nome'      => 'required|min_length[2]',
        ];
    }
}
