<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Publica notícias agendadas cuja data já venceu.
 *
 * Agendar no cron (a par do processador de notificações):
 *   * * * * * cd /caminho/projeto && php spark cms:publicar-agendados >> /dev/null 2>&1
 */
class PublicarConteudoAgendado extends BaseCommand
{
    protected $group = 'CMS';
    protected $name = 'cms:publicar-agendados';
    protected $description = 'Publica as notícias agendadas cuja data de agendamento já passou.';

    public function run(array $params)
    {
        $total = service('noticias')->publicarAgendadasVencidas();

        if ($total > 0) {
            CLI::write("Publicadas {$total} notícia(s) agendada(s).", 'green');
            log_message('info', 'cms:publicar-agendados publicou {total} notícia(s).', ['total' => $total]);
        }
    }
}
