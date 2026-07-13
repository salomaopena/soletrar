<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Parcerias;

use App\Controllers\Admin\CrudBaseController;

class PatrocinadoresController extends CrudBaseController
{
    protected function model(): string  { return 'PatrocinadorModel'; }
    protected function rota(): string   { return 'admin/parcerias/patrocinadores'; }
    protected function titulo(): string { return 'Parceiros e patrocinadores'; }
    protected function ordenarPor(): string { return 'nome'; }

    protected function colunas(): array
    {
        return [
            'nome'  => 'Nome',
            'tipo'  => 'Tipo',
            'nivel' => 'Nível',
            'ativo' => ['rotulo' => 'Ativo', 'tipo' => 'bool'],
        ];
    }

    protected function campos(): array
    {
        return [
            ['nome' => 'nome', 'rotulo' => 'Nome', 'obrigatorio' => true, 'largura' => 6],
            ['nome' => 'tipo', 'rotulo' => 'Tipo', 'tipo' => 'select', 'obrigatorio' => true, 'largura' => 3,
             'opcoes' => [
                 'ministerio'          => 'Ministério',
                 'governo_provincial'  => 'Governo provincial',
                 'empresa'             => 'Empresa',
                 'ong'                 => 'ONG',
                 'escola'              => 'Escola',
                 'biblioteca'          => 'Biblioteca',
                 'clube_leitura'       => 'Clube de leitura',
                 'media'               => 'Media',
                 'outro'               => 'Outro',
             ]],
            ['nome' => 'nivel', 'rotulo' => 'Nível', 'tipo' => 'select', 'largura' => 3,
             'opcoes' => [
                 'diamante'      => 'Diamante',
                 'ouro'          => 'Ouro',
                 'prata'         => 'Prata',
                 'bronze'        => 'Bronze',
                 'apoiador'      => 'Apoiador',
                 'institucional' => 'Institucional',
             ]],
            ['nome' => 'website', 'rotulo' => 'Website', 'largura' => 6],
            ['nome' => 'logo_url', 'rotulo' => 'URL do logótipo', 'largura' => 6],
            ['nome' => 'email', 'rotulo' => 'E-mail', 'tipo' => 'email', 'largura' => 4],
            ['nome' => 'telefone', 'rotulo' => 'Telefone', 'largura' => 4],
            ['nome' => 'contacto_pessoa', 'rotulo' => 'Pessoa de contacto', 'largura' => 4],
            ['nome' => 'descricao', 'rotulo' => 'Descrição', 'tipo' => 'textarea', 'largura' => 12],
            ['nome' => 'ativo', 'rotulo' => 'Ativo', 'tipo' => 'checkbox'],
        ];
    }

    protected function regras(): array
    {
        return [
            'nome'     => 'required|min_length[2]',
            'tipo'     => 'required',
            'email'    => 'permit_empty|valid_email',
            'telefone' => 'permit_empty|telefone_ao',
        ];
    }
}
