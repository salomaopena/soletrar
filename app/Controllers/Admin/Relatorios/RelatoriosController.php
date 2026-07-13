<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Relatorios;

use App\Controllers\Admin\AdminBaseController;

/**
 * Relatórios do concurso. Todas as consultas respeitam o escopo
 * territorial do utilizador (RelatorioService — Fase 6).
 *
 * Cada relatório tem ecrã + exportação CSV.
 */
class RelatoriosController extends AdminBaseController
{
    public function index()
    {
        $edicaoId = (int) ($this->request->getGet('edicao_id')
            ?: (model('EdicaoModel')->orderBy('ano', 'DESC')->first()->id ?? 0));

        return view('admin/relatorios/index', [
            'edicaoId'   => $edicaoId,
            'edicoes'    => model('EdicaoModel')->orderBy('ano', 'DESC')->findAll(),
            'provincias' => service('relatorios')->estatisticasProvincias($edicaoId, $this->escopo),
            'porClasse'  => $this->porClasse($edicaoId),
            'porGenero'  => $this->porGenero($edicaoId),
            'porEscola'  => $this->topEscolas($edicaoId),
            'resumo'     => $this->resumo($edicaoId),
        ]);
    }

    /** Exporta o funil por província. */
    public function exportarProvincias()
    {
        $edicaoId = (int) $this->request->getGet('edicao_id');

        return service('exportacao')->csv('relatorio-provincias', [
            'nome'                  => 'Província',
            'escolas_participantes' => 'Escolas',
            'inscricoes'            => 'Inscrições',
            'validadas'             => 'Validadas',
            'pendentes'             => 'Pendentes',
            'rejeitadas'            => 'Rejeitadas',
        ], service('relatorios')->estatisticasProvincias($edicaoId, $this->escopo));
    }

    /** Palavras com pior taxa de acerto (valor pedagógico). */
    public function palavras()
    {
        $edicaoId = (int) ($this->request->getGet('edicao_id')
            ?: (model('EdicaoModel')->orderBy('ano', 'DESC')->first()->id ?? 0));

        return view('admin/relatorios/palavras', [
            'edicaoId' => $edicaoId,
            'edicoes'  => model('EdicaoModel')->orderBy('ano', 'DESC')->findAll(),
            'palavras' => service('relatorios')->palavrasMaisDificeis($edicaoId, 50),
        ]);
    }

    // ============================ INTERNOS ============================

    private function base(int $edicaoId)
    {
        return model('InscricaoModel')
            ->join('candidatos c', 'c.id = inscricoes.candidato_id')
            ->where('inscricoes.edicao_id', $edicaoId)
            ->noEscopo($this->escopo);
    }

    private function resumo(int $edicaoId): array
    {
        $db = db_connect();

        $conta = function (?string $status) use ($edicaoId) {
            $m = model('InscricaoModel')
                ->where('inscricoes.edicao_id', $edicaoId)
                ->noEscopo($this->escopo);
            if ($status !== null) { $m->where('inscricoes.status', $status); }
            return $m->countAllResults();
        };

        return [
            'inscricoes' => $conta(null),
            'validadas'  => $conta('validada'),
            'pendentes'  => $conta('pendente'),
            'rejeitadas' => $conta('rejeitada'),
            'eventos'    => $db->table('eventos_competicao ev')
                ->join('fases_concurso f', 'f.id = ev.fase_id')
                ->where('f.edicao_id', $edicaoId)->countAllResults(),
            'palavras_validadas' => $db->table('palavras')
                ->where('validada', 1)->where('deleted_at', null)->countAllResults(),
        ];
    }

    private function porClasse(int $edicaoId): array
    {
        return $this->base($edicaoId)
            ->select('c.classe_atual AS classe, COUNT(*) AS total')
            ->groupBy('c.classe_atual')
            ->orderBy('c.classe_atual')
            ->findAll();
    }

    private function porGenero(int $edicaoId): array
    {
        return $this->base($edicaoId)
            ->select('c.genero, COUNT(*) AS total')
            ->groupBy('c.genero')
            ->findAll();
    }

    private function topEscolas(int $edicaoId): array
    {
        return $this->base($edicaoId)
            ->select('e.nome AS escola, p.nome AS provincia, COUNT(*) AS total')
            ->join('escolas e', 'e.id = inscricoes.escola_id')
            ->join('provincias p', 'p.id = inscricoes.provincia_id')
            ->groupBy('e.id, e.nome, p.nome')
            ->orderBy('total', 'DESC')
            ->findAll(15);
    }
}
