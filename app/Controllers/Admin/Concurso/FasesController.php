<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Concurso;

use App\Controllers\Admin\CrudBaseController;

/** Fases do concurso (escolar → provincial → nacional) e vagas de progressão. */
class FasesController extends CrudBaseController
{
    protected function model(): string  { return 'FaseModel'; }
    protected function rota(): string   { return 'admin/fases'; }
    protected function titulo(): string { return 'Fases do concurso'; }
    protected function ordenarPor(): string { return 'ordem'; }

    protected function colunas(): array
    {
        return [
            'nome'               => 'Fase',
            'tipo_fase'          => 'Tipo',
            'ordem'              => 'Ordem',
            'vagas_proxima_fase' => 'Vagas p/ fase seguinte',
            'status'             => ['rotulo' => 'Estado', 'tipo' => 'badge'],
        ];
    }

    protected function campos(): array
    {
        return [
            ['nome' => 'edicao_id', 'rotulo' => 'Edição', 'tipo' => 'select', 'obrigatorio' => true,
             'largura' => 6, 'opcoes' => $this->opcoes('EdicaoModel')],
            ['nome' => 'nome', 'rotulo' => 'Nome da fase', 'obrigatorio' => true, 'largura' => 6],
            ['nome' => 'tipo_fase', 'rotulo' => 'Tipo', 'tipo' => 'select', 'obrigatorio' => true, 'largura' => 4,
             'opcoes' => [
                 'escolar'            => 'Escolar',
                 'municipal'          => 'Municipal',
                 'provincial'         => 'Provincial',
                 'semifinal_nacional' => 'Semifinal nacional',
                 'final_nacional'     => 'Final nacional',
             ]],
            ['nome' => 'ordem', 'rotulo' => 'Ordem', 'tipo' => 'number', 'obrigatorio' => true, 'largura' => 4],
            ['nome' => 'vagas_proxima_fase', 'rotulo' => 'Vagas p/ fase seguinte', 'tipo' => 'number', 'largura' => 4,
             'ajuda' => 'Quantos apuram. 0 na fase final.'],
            ['nome' => 'status', 'rotulo' => 'Estado', 'tipo' => 'select', 'largura' => 4,
             'opcoes' => ['agendada' => 'Agendada', 'em_curso' => 'Em curso',
                          'concluida' => 'Concluída', 'cancelada' => 'Cancelada']],
            ['nome' => 'data_inicio', 'rotulo' => 'Início', 'tipo' => 'date', 'largura' => 4],
            ['nome' => 'data_fim', 'rotulo' => 'Fim', 'tipo' => 'date', 'largura' => 4],
            ['nome' => 'descricao', 'rotulo' => 'Descrição', 'tipo' => 'textarea', 'largura' => 12],
            ['nome' => 'regras_especificas', 'rotulo' => 'Regras específicas desta fase',
             'tipo' => 'textarea', 'largura' => 12, 'linhas' => 3,
             'ajuda' => 'Ex.: nesta fase não se permite pedir etimologia.'],
        ];
    }

    protected function regras(): array
    {
        return [
            'edicao_id' => 'required|is_natural_no_zero',
            'nome'      => 'required|min_length[3]',
            'tipo_fase' => 'required|in_list[escolar,municipal,provincial,semifinal_nacional,final_nacional]',
            'ordem'     => 'required|is_natural_no_zero',
        ];
    }
}
