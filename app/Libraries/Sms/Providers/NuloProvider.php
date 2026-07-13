<?php

declare(strict_types=1);

namespace App\Libraries\Sms\Providers;

use App\Libraries\Sms\SmsMensagem;
use App\Libraries\Sms\SmsProviderInterface;
use App\Libraries\Sms\SmsResultado;

/**
 * Provedor nulo para desenvolvimento e testes: não envia nada,
 * regista no log e devolve sucesso. Ativo quando pro2sms.ativo = false.
 */
final class NuloProvider implements SmsProviderInterface
{
    public function nome(): string
    {
        return 'nulo';
    }

    public function enviar(SmsMensagem $mensagem): SmsResultado
    {
        log_message('info', '[SMS SIMULADO] Para {tel}: {texto}', [
            'tel' => $mensagem->telefone, 'texto' => $mensagem->texto,
        ]);

        return SmsResultado::ok('simulado-' . bin2hex(random_bytes(6)), 0.0, ['simulado' => true]);
    }
}
