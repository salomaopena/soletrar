<?php

declare(strict_types=1);

namespace App\Controllers\Publico;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Páginas institucionais (slug). Padrão: idêntico ao ver() de notícias.
 */
class PaginasController extends BaseController
{
    public function ver(string $slug)
    {
        $pagina = model('PaginaModel')
            ->where('slug', $slug)->where('status', 'publicada')->first()
            ?? throw PageNotFoundException::forPageNotFound();

        return view('publico/paginas/ver', ['pagina' => $pagina]);
    }
}
