<?php

declare(strict_types=1);

namespace App\Controllers\Publico;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Resultados públicos. A view publico/resultados/evento.php já existe (Fase 8);
 * este controller prepara os dados a partir do RelatorioService (Fase 6).
 */
class ResultadosController extends BaseController
{
    public function index()
    {
        // TODO: listar edições/eventos com resultados homologados.
        return view('publico/resultados/index', [
            'eventos' => model('EventoModel')->where('status', 'concluido')
                ->orderBy('data_evento', 'DESC')->findAll(),
        ]);
    }

    public function evento(int $eventoId)
    {
        $evento = model('EventoModel')->find($eventoId)
            ?? throw PageNotFoundException::forPageNotFound();

        // A grelha vem "achatada" do service; reorganizar por candidato/round.
        $grelhaPlana = service('relatorios')->grelhaRounds($eventoId);
        $grelha = [];
        $maxRound = 0;
        foreach ($grelhaPlana as $linha) {
            $pid = $linha['participacao_id'];
            $grelha[$pid]['nome']               = $linha['nome_completo'];
            $grelha[$pid]['numero_concorrente'] = $linha['numero_concorrente'];
            $grelha[$pid]['rounds'][(int) $linha['numero_round']] = $linha;
            $maxRound = max($maxRound, (int) $linha['numero_round']);
        }

        return view('publico/resultados/evento', [
            'evento'         => $evento,
            'classificacao'  => service('relatorios')->classificacaoEvento($eventoId),
            'grelha'         => $grelha,
            'totalRounds'    => $maxRound,
        ]);
    }

    public function edicao(int $edicaoId)
    {
        // TODO: agregado de resultados de uma edição inteira.
        return view('publico/resultados/index', ['eventos' => []]);
    }
}
