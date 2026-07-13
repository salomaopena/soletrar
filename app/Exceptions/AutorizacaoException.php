<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Lançada quando o utilizador tem a permissão Shield necessária,
 * mas o registo pedido está FORA do seu escopo territorial
 * (ex.: coordenador de Benguela a aceder a inscrição do Huambo).
 *
 * Tratada centralmente → resposta 403 + registo em auditoria.
 */
class AutorizacaoException extends RuntimeException
{
}
