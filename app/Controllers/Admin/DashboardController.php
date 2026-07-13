<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

/**
 * Painel administrativo. A view admin/dashboard/index.php já existe (Fase 8).
 */
class DashboardController extends AdminBaseController
{
    public function index()
    {
        $edicao = model('EdicaoModel')->orderBy('ano', 'DESC')->first();
        $edicaoId = $edicao->id ?? 0;

        // Estatísticas já filtradas pelo escopo territorial do utilizador.
        $inscricoesBase = model('InscricaoModel')->noEscopo($this->escopo)->where('edicao_id', $edicaoId);

        $stats = [
            'edicao_nome' => $edicao->nome ?? 'Sem edição ativa',
            'inscricoes'  => (clone $inscricoesBase)->countAllResults(false),
            'validadas'   => (clone $inscricoesBase)->where('status', 'validada')->countAllResults(false),
            'pendentes'   => (clone $inscricoesBase)->where('status', 'pendente')->countAllResults(false),
            'escolas'     => model('EscolaModel')->where('ativo', 1)->countAllResults(),
        ];

        return view('admin/dashboard/index', [
            'stats'              => $stats,
            'inscricoesRecentes' => model('InscricaoModel')->comCandidatoEEscola()
                ->noEscopo($this->escopo)->orderBy('inscricoes.data_inscricao', 'DESC')->findAll(6),
            'proximosEventos'    => model('EventoModel')->where('data_evento >=', utc_agora())
                ->orderBy('data_evento')->findAll(5),
        ]);
    }

    public function semAtribuicao()
    {
        return view('admin/sistema/sem_atribuicao');
    }

}
