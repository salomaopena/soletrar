<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Regista em auditoria toda a requisição de ESCRITA bem-sucedida na
 * área administrativa (POST/PUT/PATCH/DELETE com resposta 2xx/3xx).
 *
 * Complementa (não substitui) o trait Auditavel dos models:
 *  - o trait regista O QUE mudou (antes/depois por tabela);
 *  - este filtro regista QUE AÇÃO HTTP foi feita (rota completa),
 *    apanhando também ações sem escrita em BD (ex.: exportações).
 */
class AuditoriaFilter implements FilterInterface
{
    private const METODOS_ESCRITA = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function before(RequestInterface $request, $arguments = null)
    {
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (! in_array(strtoupper($request->getMethod()), self::METODOS_ESCRITA, true)) {
            return;
        }

        $codigo = $response->getStatusCode();
        if ($codigo >= 400) {
            return; // falhas não são "ações realizadas"
        }

        service('auditoria')->registar(
            acao: 'http_' . strtolower($request->getMethod()),
            entidade: 'rota',
            descricao: $request->getPath(),
        );
    }
}
