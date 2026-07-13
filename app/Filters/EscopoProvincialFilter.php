<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Resolve o escopo territorial do utilizador autenticado UMA vez por
 * request e bloqueia contas de coordenação sem atribuição ativa.
 *
 * Corre depois do SessionAuth do Shield (ordem no grupo de rotas admin).
 */
class EscopoProvincialFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! auth()->loggedIn()) {
            return redirect()->to('login');
        }

        $escopo = service('escopo')->doUtilizador(auth()->id());

        // Coordenadores (não-nacionais) sem qualquer território ativo não
        // podem operar — evita ecrãs vazios confusos e fugas por engano.
        $ehCoordenador = auth()->user()->inGroup(
            'coord_provincial', 'coord_municipal', 'coord_escolar'
        );

        if ($ehCoordenador && $escopo->nivel !== 'nacional'
            && $escopo->provincias === [] && $escopo->escolas === []) {
            return redirect()->to('admin/sem-atribuicao');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
