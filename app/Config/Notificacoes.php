<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuração da camada de notificações.
 *
 * .env:
 *   notificacoes.email.remetente     = nao-responder@soletracao.ao
 *   notificacoes.email.remetenteNome = Concurso Nacional de Soletração
 */
class Notificacoes extends BaseConfig
{
    public string $emailRemetente     = 'nao-responder@soletracao.ao';
    public string $emailRemetenteNome = 'Concurso Nacional de Soletração';

    /** Nº máximo de tentativas de envio por mensagem (fila). */
    public int $maxTentativas = 3;

    /**
     * Backoff entre tentativas, em segundos: 1.ª falha → +60 s,
     * 2.ª → +300 s, 3.ª → +1500 s. Progressão ~5x: transiente resolve-se
     * no 1.º retry; indisponibilidade prolongada não martela o provedor.
     */
    public array $backoffSegundos = [60, 300, 1500];

    /** Tamanho do lote processado por execução do worker. */
    public int $tamanhoLote = 50;
}
