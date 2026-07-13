<?php

declare(strict_types=1);

namespace App\Libraries\Sms;

/**
 * Contrato de qualquer provedor de SMS.
 *
 * REGRA: enviar() NUNCA lança exceção por falha de rede/provedor —
 * devolve SmsResultado::falha(). Exceções ficam reservadas a erros de
 * programação. Isto simplifica o worker da fila: falha → retry.
 */
interface SmsProviderInterface
{
    public function enviar(SmsMensagem $mensagem): SmsResultado;

    /** Identificador gravado em logs_sms.provider (ex.: 'pro2sms'). */
    public function nome(): string;
}
