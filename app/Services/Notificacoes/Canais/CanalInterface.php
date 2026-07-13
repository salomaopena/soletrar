<?php

declare(strict_types=1);

namespace App\Services\Notificacoes\Canais;

/**
 * Contrato dos canais de envio consumidos pelo worker da fila.
 * enviar() devolve true/false (falso → a fila decide o retry) e é
 * responsável por escrever o SEU log (logs_email / logs_sms).
 */
interface CanalInterface
{
    /** @param array $item Linha de notificacoes_fila */
    public function enviar(array $item): bool;
}
