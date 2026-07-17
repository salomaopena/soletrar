<?php

declare(strict_types=1);

namespace App\Controllers\Publico;

use App\Controllers\BaseController;

/**
 * Página inicial do portal.
 *
 * resultados recentes (usar model('NoticiaModel')->publicas()->ordemPortal()
 * e model('EventoModel')). Padrão: ver Publico\NoticiasController.
 */
class HomeController extends BaseController
{
    public function index()
    {
        return view('publico/home/index', [
            'destaques'      => model('NoticiaModel')->publicas()->ordemPortal()->limit(3)->find(),
            'patrocinadores' => $this->patrocinadoresPorNivel(),
        ]);
    }

    /** Patrocinadores ativos, ordenados por nível de patrocínio (do mais alto ao mais baixo). */
    private function patrocinadoresPorNivel(): array
    {
        $ordem = ['diamante' => 1, 'ouro' => 2, 'prata' => 3, 'bronze' => 4,
                  'apoiador' => 5, 'institucional' => 6];

        $patrocinadores = model('PatrocinadorModel')
            ->where('ativo', 1)
            ->orderBy('nome')
            ->findAll();

        usort($patrocinadores, static fn ($a, $b) =>
            ($ordem[$a->nivel] ?? 9) <=> ($ordem[$b->nivel] ?? 9));

        return $patrocinadores;
    }
}
