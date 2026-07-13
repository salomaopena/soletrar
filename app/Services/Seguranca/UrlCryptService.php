<?php

declare(strict_types=1);

namespace App\Services\Seguranca;

use App\Exceptions\TokenInvalidoException;
use CodeIgniter\Encryption\EncrypterInterface;
use CodeIgniter\Encryption\Exceptions\EncryptionException;
use Config\UrlCrypt as UrlCryptConfig;

/**
 * Serviço reutilizável de encriptação/assinatura de parâmetros de URL.
 *
 * Objetivo: nunca expor IDs sequenciais em rotas sensíveis.
 *
 * Garantias oferecidas:
 *  - Confidencialidade + integridade: AES-256-CTR com HMAC-SHA512
 *    (Encrypter nativo do CI4). Um token adulterado NUNCA decifra.
 *  - Ligação ao contexto: o token de uma inscrição não é aceite numa
 *    rota de candidato — impede "troca de tokens" entre recursos.
 *  - Expiração opcional (TTL): essencial para links em e-mail/SMS.
 *  - Codificação base64url: seguro em URLs sem percent-encoding.
 *
 * Uso típico:
 *   $token = service('urlCrypt')->cifrar($inscricao->id, 'inscricao');
 *   $id    = service('urlCrypt')->decifrar($token, 'inscricao');
 */
final class UrlCryptService
{
    public function __construct(
        private readonly EncrypterInterface $encrypter,
        private readonly UrlCryptConfig $config,
    ) {
    }

    /**
     * Cifra um identificador para uso em URL.
     *
     * @param int|string  $id       ID (ou outro identificador) a proteger
     * @param string      $contexto Nome do recurso (ex.: 'inscricao', 'candidato')
     * @param int|null    $ttl      Segundos até expirar; null usa o ttlPadrao da config
     */
    public function cifrar(int|string $id, string $contexto, ?int $ttl = null): string
    {
        $ttl ??= $this->config->ttlPadrao;

        $payload = json_encode([
            'v' => $id,                                    // valor
            'c' => $contexto,                              // contexto do recurso
            'e' => $ttl === null ? 0 : time() + $ttl,      // expiração (0 = nunca)
        ], JSON_THROW_ON_ERROR);

        return $this->base64UrlCodificar($this->encrypter->encrypt($payload));
    }

    /**
     * Atalho para links enviados por e-mail/SMS (TTL de 72 h por omissão).
     */
    public function cifrarLinkExterno(int|string $id, string $contexto): string
    {
        return $this->cifrar($id, $contexto, $this->config->ttlLinksExternos);
    }

    /**
     * Decifra e valida um token de URL.
     *
     * @throws TokenInvalidoException Se o token for adulterado, de outro
     *                                contexto ou estiver expirado.
     */
    public function decifrar(string $token, string $contexto): int|string
    {
        $binario = $this->base64UrlDescodificar($token);

        if ($binario === false) {
            throw TokenInvalidoException::porMotivo('base64url malformado');
        }

        try {
            $payload = $this->encrypter->decrypt($binario);
        } catch (EncryptionException) {
            // Token adulterado ou cifrado com outra chave.
            throw TokenInvalidoException::porMotivo("falha de decifra no contexto '{$contexto}'");
        }

        $dados = json_decode($payload, true);

        if (! is_array($dados) || ! isset($dados['v'], $dados['c'], $dados['e'])) {
            throw TokenInvalidoException::porMotivo('payload inesperado');
        }

        if (! hash_equals((string) $dados['c'], $contexto)) {
            throw TokenInvalidoException::porMotivo(
                "contexto errado: esperado '{$contexto}', recebido '{$dados['c']}'"
            );
        }

        if ((int) $dados['e'] !== 0 && time() > (int) $dados['e']) {
            throw TokenInvalidoException::porMotivo("token expirado no contexto '{$contexto}'");
        }

        return $dados['v'];
    }

    /**
     * Gera um token aleatório opaco e URL-safe (NÃO reversível).
     * Uso: confirmação de newsletter, reposição de senha, convites.
     */
    public function gerarTokenOpaco(int $bytes = 32): string
    {
        return $this->base64UrlCodificar(random_bytes($bytes));
    }

    // ----------------------------------------------------------------
    // base64url (RFC 4648 §5): substitui +/ por -_ e remove padding
    // ----------------------------------------------------------------

    private function base64UrlCodificar(string $dados): string
    {
        return rtrim(strtr(base64_encode($dados), '+/', '-_'), '=');
    }

    private function base64UrlDescodificar(string $dados): string|false
    {
        return base64_decode(strtr($dados, '-_', '+/'), true);
    }
}
