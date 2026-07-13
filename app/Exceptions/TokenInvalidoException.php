<?php

declare(strict_types=1);

namespace App\Exceptions;

use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Lançada quando um parâmetro de URL cifrado é inválido, adulterado,
 * de contexto errado ou expirado.
 *
 * Estende PageNotFoundException de propósito: o tratador de exceções
 * do CI4 converte-a automaticamente numa página 404 amigável, sem
 * revelar ao utilizador (ou a um atacante) o motivo técnico da falha.
 */
class TokenInvalidoException extends PageNotFoundException
{
    public static function porMotivo(string $motivoInterno): static
    {
        // O motivo interno vai para o log; a mensagem pública é genérica.
        log_message('warning', 'Token de URL inválido: {motivo} | IP: {ip}', [
            'motivo' => $motivoInterno,
            'ip'     => service('request')->getIPAddress(),
        ]);

        return new static(lang('Geral.linkInvalido')); // "Este link é inválido ou expirou."
    }
}
