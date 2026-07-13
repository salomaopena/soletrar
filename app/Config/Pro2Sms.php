<?php


namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Integração com a API de SMS https://pro2sms.ao
 *
 * TODOS os valores vêm do .env (nunca versionar credenciais):
 *   pro2sms.baseUrl   = https://pro2sms.ao/api
 *   pro2sms.apiKey    = ********
 *   pro2sms.senderId  = SOLETRACAO
 *   pro2sms.timeout   = 10
 *   pro2sms.ativo     = true
 *
 * NOTA DE IMPLEMENTAÇÃO: o endpoint e os nomes de campos do payload
 * estão centralizados AQUI e no Pro2SmsProvider, marcados com [AJUSTAR],
 * para serem afinados com a documentação oficial/credenciais reais sem
 * tocar em mais nenhum ficheiro.
 */
class Pro2Sms extends BaseConfig
{
    public string $baseUrl = 'https://pro2sms.ao/api';

    /** Chave de API fornecida pela pro2sms. */
    public string $apiKey = '';

    /** Sender ID aprovado (máx. 11 caracteres alfanuméricos). */
    public string $senderId = 'SOLETRACAO';

    /** Timeout HTTP em segundos (o worker corre em CLI; 10 s é seguro). */
    public int $timeout = 10;

    /** Interruptor geral: false em desenvolvimento usa o NuloProvider. */
    public bool $ativo = false;

    /** Caminho do endpoint de envio. [AJUSTAR] conforme documentação. */
    public string $endpointEnvio = '/v1/sms/send';
}
