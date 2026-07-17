<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rate limit por IP usando o Throttler nativo (token bucket).
 *
 * Uso nas rotas (argumentos = pedidos, janela em minutos):
 *   ['filter' => 'throttle:10,1']   → 10 pedidos por minuto
 *   ['filter' => 'throttle:5,10']   → 5 pedidos por 10 minutos
 */
class ThrottleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $limite = (int) ($arguments[0] ?? 30);
        $janela = 60 * (int) ($arguments[1] ?? 1);

        $throttler = service('throttler');

        // O balde é isolado por IP e por rota, para que abusar do login
        // não consuma a quota da inscrição pública, e vice-versa.
        $chave = 'throttle_' . md5($request->getIPAddress() . '|' . $request->getPath());

        if ($throttler->check($chave, $limite, $janela) === false) {
            log_message('warning', 'Rate limit excedido: {ip} em {rota}', [
                'ip'   => $request->getIPAddress(),
                'rota' => $request->getPath(),
            ]);

            $espera = $throttler->getTokenTime();

            return service('response')
                ->setStatusCode(429, lang('Geral.demasiadosPedidos'))
                ->setHeader('Retry-After', (string) $espera)
                ->setBody(view('errors/html/error_429', ['tentarDaquiA' => $espera]));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
