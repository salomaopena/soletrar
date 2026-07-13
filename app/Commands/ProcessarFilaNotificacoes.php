<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Worker da fila de notificações.
 *
 * Cron (a cada minuto):
 *   * * * * * cd /var/www/soletracao && php spark notificacoes:processar >> /dev/null 2>&1
 *
 * Opções:
 *   --lote N   processa até N mensagens nesta execução (padrão: config)
 */
class ProcessarFilaNotificacoes extends BaseCommand
{
    protected $group = 'Notificações';
    protected $name = 'notificacoes:processar';
    protected $description = 'Processa a fila de e-mails e SMS pendentes (com retries e backoff).';
    protected $options = ['--lote' => 'Tamanho do lote a processar'];

    public function run(array $params)
    {
        $fila = service('filaNotificacoes');
        $lote = $fila->reclamarLote(isset($params['lote']) ? (int) $params['lote'] : null);

        if ($lote === []) {
            return;   // silencioso: corre a cada minuto
        }

        $canais = [
            'email' => service('canalEmail'),
            'sms' => service('canalSms'),
            'sistema' => service('canalSistema'),
        ];

        $ok = $falhas = 0;

        foreach ($lote as $item) {
            $item['dados_json'] = json_decode($item['dados_json'] ?? '[]', true) ?? [];

            try {
                $sucesso = $canais[$item['canal']]->enviar($item);
            } catch (Throwable $e) {
                // Um canal a lançar é bug — mas UMA mensagem envenenada
                // nunca pode parar a fila inteira.
                log_message('critical', 'Fila #{id}: exceção no canal {canal}: {erro}', [
                    'id' => $item['id'],
                    'canal' => $item['canal'],
                    'erro' => $e->getMessage(),
                ]);
                $sucesso = false;
            }

            if ($sucesso) {
                $fila->marcarEnviada((int) $item['id']);
                $ok++;
            } else {
                $fila->marcarFalha($item, 'Envio devolveu falha (ver logs do canal).');
                $falhas++;
            }
        }

        CLI::write("Fila processada: {$ok} enviada(s), {$falhas} falha(s)/retry(s).", $falhas ? 'yellow' : 'green');
    }
}
