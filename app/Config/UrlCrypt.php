<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuração da encriptação de parâmetros de URL.
 *
 * A chave DEVE ser diferente da encryption.key geral da aplicação,
 * para que a rotação de uma não invalide a outra.
 *
 * .env:
 *   urlcrypt.chave = hex2bin:0a1b2c... (gerar com: php spark key:generate --show)
 */
class UrlCrypt extends BaseConfig
{
    /** Chave dedicada (lida do .env — nunca versionar o valor real). */
    public string $chave = '';

    /** Driver de encriptação do CI4. OpenSSL = AES-256-CTR + HMAC-SHA512. */
    public string $driver = 'OpenSSL';

    /**
     * TTL padrão, em segundos, para tokens gerados SEM ttl explícito.
     * null = tokens de navegação não expiram (recomendado para o backoffice,
     * onde a sessão já limita o acesso).
     */
    public ?int $ttlPadrao = null;

    /** TTL padrão para links enviados por e-mail/SMS (72 horas). */
    public int $ttlLinksExternos = 259200;

    public function __construct()
    {
        parent::__construct();

        // Lê a chave do .env, se não estiver vazia.
        $chave = env('urlcrypt.chave');
        if (!empty($chave)) {
            $this->chave = $chave;
        }
    }
}
