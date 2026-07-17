<?php

declare(strict_types=1);

namespace App\Controllers\Publico;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Resultados públicos.
 *
 * REGRA DE NEGÓCIO (documentada desde a Fase 1 e reforçada aqui): a
 * classificação de um evento só é PÚBLICA depois de HOMOLOGADA — nunca
 * apenas "concluída". Um evento concluído tem uma classificação
 * PROVISÓRIA (pode ainda ser recalculada, corrigida por apelação, etc.);
 * só a homologação a sela.
 *
 * Não existe uma coluna "homologado" em `eventos_competicao` — o sinal
 * fidedigno é o registo em `auditoria_logs` que
 * ClassificacaoService::homologar() grava (acao='homologar_resultados').
 * Os métodos abaixo filtram sempre por esse registo.
 */
class ResultadosController extends BaseController
{
    /** Lista as EDIÇÕES que já têm pelo menos um evento homologado. */
    public function index()
    {
        $edicoes = db_connect()->query(
            'SELECT ed.id, ed.nome, ed.ano,
                    COUNT(DISTINCT ev.id) AS eventos_homologados
               FROM edicoes_concurso ed
               JOIN fases_concurso f       ON f.edicao_id = ed.id
               JOIN eventos_competicao ev  ON ev.fase_id = f.id
              WHERE EXISTS (
                    SELECT 1 FROM auditoria_logs al
                     WHERE al.entidade = "eventos_competicao"
                       AND al.entidade_id = ev.id
                       AND al.acao = "homologar_resultados"
                )
              GROUP BY ed.id, ed.nome, ed.ano
              ORDER BY ed.ano DESC'
        )->getResult();

        return view('publico/resultados/index', ['edicoes' => $edicoes]);
    }

    /**
     * Agregado de resultados de uma edição: os eventos homologados,
     * agrupados por fase (na ordem escolar → provincial → nacional),
     * cada um com o pódio resumido e link para a classificação completa.
     */
    public function edicao(int $edicaoId)
    {
        $edicao = model('EdicaoModel')->find($edicaoId)
            ?? throw PageNotFoundException::forPageNotFound();

        // IMPORTANTE: usa-se EXISTS, não JOIN, para verificar a homologação.
        // Um JOIN normal multiplicaria o evento por cada registo de
        // auditoria correspondente — e nada impede (nem impedia, antes de
        // uma correção nesta mesma versão) que um evento fosse homologado
        // mais do que uma vez, gerando vários registos. Com EXISTS, o
        // evento aparece exatamente UMA vez, seja qual for o número de
        // registos de auditoria que tenha.
        $eventos = db_connect()->query(
            'SELECT ev.id, ev.nome, ev.data_evento,
                    f.nome AS fase_nome, f.ordem AS fase_ordem,
                    cat.nome AS categoria_nome
               FROM eventos_competicao ev
               JOIN fases_concurso f      ON f.id = ev.fase_id
          LEFT JOIN categorias_competicao cat ON cat.id = ev.categoria_id
              WHERE f.edicao_id = ?
                AND EXISTS (
                    SELECT 1 FROM auditoria_logs al
                     WHERE al.entidade = "eventos_competicao"
                       AND al.entidade_id = ev.id
                       AND al.acao = "homologar_resultados"
                )
              ORDER BY f.ordem, ev.data_evento',
            [$edicaoId]
        )->getResult();

        // Pódio resumido (top 3) de cada evento homologado — evita N+1
        // fazendo uma única consulta e agrupando em PHP.
        $eventoIds = array_column($eventos, 'id');
        $podios    = [];

        if ($eventoIds !== []) {
            $linhas = db_connect()->table('participacoes pa')
                ->select('pa.evento_id, pa.posicao_final, c.nome_completo')
                ->join('inscricoes i', 'i.id = pa.inscricao_id')
                ->join('candidatos c', 'c.id = i.candidato_id')
                ->whereIn('pa.evento_id', $eventoIds)
                ->where('pa.posicao_final <=', 3)
                ->orderBy('pa.evento_id')->orderBy('pa.posicao_final')
                ->get()->getResultArray();

            foreach ($linhas as $l) {
                $podios[$l['evento_id']][] = $l;
            }
        }

        // Agrupar eventos por fase, para a view desenhar secções.
        $porFase = [];
        foreach ($eventos as $ev) {
            $porFase[$ev->fase_nome][] = $ev;
        }

        return view('publico/resultados/edicao', [
            'edicao'   => $edicao,
            'porFase'  => $porFase,
            'podios'   => $podios,
        ]);
    }

    public function evento(int $eventoId)
    {
        $evento = model('EventoModel')->find($eventoId)
            ?? throw PageNotFoundException::forPageNotFound();

        // Um evento só concluído (não homologado) não tem classificação
        // pública — evita expor uma pauta provisória/ainda corrigível.
        $homologado = db_connect()->table('auditoria_logs')
            ->where('entidade', 'eventos_competicao')
            ->where('entidade_id', $eventoId)
            ->where('acao', 'homologar_resultados')
            ->countAllResults() > 0;

        if (! $homologado) {
            throw PageNotFoundException::forPageNotFound();
        }

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
}
