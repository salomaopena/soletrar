<?php

declare(strict_types=1);

/**
 * Helper de datas — fachada fina sobre o DataHoraService (Fase 3).
 * Registar em Autoload.php. É o ÚNICO caminho de formatação nas views.
 */

if (! function_exists('utc_agora')) {
    /** DATETIME atual em UTC, no formato do MySQL (para inserts diretos). */
    function utc_agora(): string
    {
        return service('dataHora')->agoraUtc()->toDateTimeString();
    }
}

if (! function_exists('data_exibir')) {
    /** Formata uma data (UTC) para exibição em hora de Angola. */
    function data_exibir(\CodeIgniter\I18n\Time|string|null $valor, string $formato = 'curta'): string
    {
        return service('dataHora')->paraExibicao($valor, $formato);
    }
}

if (! function_exists('idade')) {
    /** Idade em anos à data de hoje. */
    function idade(\CodeIgniter\I18n\Time|string $nascimento): int
    {
        return service('dataHora')->idadeEm($nascimento, service('dataHora')->agoraUtc());
    }
}
