<?php

declare(strict_types=1);

namespace App\Controllers\Publico;

use App\Controllers\BaseController;

/**
 * Página inicial do portal.
 *
 * TODO: montar a home com notícias em destaque, próximos eventos e
 * resultados recentes (usar model('NoticiaModel')->publicas()->ordemPortal()
 * e model('EventoModel')). Padrão: ver Publico\NoticiasController.
 */
class HomeController extends BaseController
{
    public function index()
    {
        return view('publico/home/index', [
            'destaques' => model('NoticiaModel')->publicas()->ordemPortal()->limit(3)->find(),
        ]);
    }
}
