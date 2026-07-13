<?php

declare(strict_types=1);

/**
 * Helper de parâmetros encriptados na URL.
 * Carregado globalmente em app/Config/Autoload.php ($helpers).
 *
 * Nas views usa-se sempre rota_segura(); nos controllers, id_decifrar().
 */

use App\Services\Seguranca\UrlCryptService;


function debugger($data, $die = true)
{
    echo ('<pre>');
    echo (str_repeat('=', 50) . '<br>');
    echo print_r($data, true);
    echo ('<br>');
    echo (str_repeat('=', 50) . '<br>');
    echo ('</pre>');
    if ($die) {
        die(1);
    }
}


if (! function_exists('id_cifrar')) {
    /** Cifra um ID para uso em URL. */
    function id_cifrar(int|string $id, string $contexto, ?int $ttl = null): string
    {
        /** @var UrlCryptService $servico */
        $servico = service('urlCrypt');

        return $servico->cifrar($id, $contexto, $ttl);
    }
}

if (! function_exists('id_decifrar')) {
    /**
     * Decifra um ID vindo da URL.
     * Lança TokenInvalidoException (→ 404 amigável) se inválido.
     */
    function id_decifrar(string $token, string $contexto): int|string
    {
        /** @var UrlCryptService $servico */
        $servico = service('urlCrypt');

        return $servico->decifrar($token, $contexto);
    }
}

if (! function_exists('rota_segura')) {
    /**
     * Constrói a URL completa de uma rota com o ID já cifrado.
     *
     * Exemplo na view:
     *   <a href="<?= rota_segura('admin/inscricoes/ver', $inscricao->id, 'inscricao') ?>">
     */
    function rota_segura(string $rota, int|string $id, string $contexto, ?int $ttl = null): string
    {
        return site_url(rtrim($rota, '/') . '/' . id_cifrar($id, $contexto, $ttl));
    }
}
