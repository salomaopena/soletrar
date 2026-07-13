<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Capacitacoes;

use App\Controllers\Admin\CrudBaseController;

/** Formação de professores, jurados e pronunciadores. */
class CapacitacoesController extends CrudBaseController
{
    protected function model(): string  { return 'CapacitacaoModel'; }
    protected function rota(): string   { return 'admin/capacitacoes'; }
    protected function titulo(): string { return 'Capacitações'; }
    protected function ordenarPor(): string { return 'data_inicio'; }

    protected function colunas(): array
    {
        return [
            'titulo'      => 'Capacitação',
            'publico_alvo'=> 'Público-alvo',
            'modalidade'  => 'Modalidade',
            'data_inicio' => ['rotulo' => 'Início', 'tipo' => 'data'],
            'status'      => ['rotulo' => 'Estado', 'tipo' => 'badge'],
        ];
    }

    protected function campos(): array
    {
        return [
            ['nome' => 'titulo', 'rotulo' => 'Título', 'obrigatorio' => true, 'largura' => 8],
            ['nome' => 'edicao_id', 'rotulo' => 'Edição', 'tipo' => 'select', 'largura' => 4,
             'opcoes' => $this->opcoes('EdicaoModel')],
            ['nome' => 'publico_alvo', 'rotulo' => 'Público-alvo', 'largura' => 6,
             'ajuda' => 'Ex.: professores, jurados, pronunciadores'],
            ['nome' => 'modalidade', 'rotulo' => 'Modalidade', 'tipo' => 'select', 'largura' => 3,
             'opcoes' => ['presencial' => 'Presencial', 'online' => 'Online', 'hibrida' => 'Híbrida']],
            ['nome' => 'status', 'rotulo' => 'Estado', 'tipo' => 'select', 'largura' => 3,
             'opcoes' => [
                 'agendada'           => 'Agendada',
                 'inscricoes_abertas' => 'Inscrições abertas',
                 'em_curso'           => 'Em curso',
                 'concluida'          => 'Concluída',
                 'cancelada'          => 'Cancelada',
             ]],
            ['nome' => 'local_id', 'rotulo' => 'Local', 'tipo' => 'select', 'largura' => 6,
             'opcoes' => $this->opcoes('LocalEventoModel')],
            ['nome' => 'link_online', 'rotulo' => 'Link (se online)', 'largura' => 6],
            ['nome' => 'data_inicio', 'rotulo' => 'Início', 'tipo' => 'date', 'largura' => 3],
            ['nome' => 'data_fim', 'rotulo' => 'Fim', 'tipo' => 'date', 'largura' => 3],
            ['nome' => 'carga_horaria', 'rotulo' => 'Carga horária', 'tipo' => 'number', 'largura' => 3],
            ['nome' => 'vagas', 'rotulo' => 'Vagas', 'tipo' => 'number', 'largura' => 3],
            ['nome' => 'formador_principal', 'rotulo' => 'Formador principal', 'largura' => 6],
            ['nome' => 'material_apoio_url', 'rotulo' => 'Material de apoio (URL)', 'largura' => 6],
            ['nome' => 'descricao', 'rotulo' => 'Descrição', 'tipo' => 'textarea', 'largura' => 12],
        ];
    }

    protected function regras(): array
    {
        return ['titulo' => 'required|min_length[3]'];
    }
}
