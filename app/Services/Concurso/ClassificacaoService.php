<?php

declare(strict_types=1);

namespace App\Services\Concurso;

use CodeIgniter\Database\ConnectionInterface;
use RuntimeException;

/**
 * Classificação final de um evento.
 *
 * Critérios de ordenação (regulamento; do mais forte para o mais fraco):
 *  1. sobreviveu até mais tarde (eliminado_round DESC; NULL = venceu);
 *  2. maior pontuação total;
 *  3. menor tempo total de resposta;
 *  4. persistindo empate → round de desempate (decisão humana).
 *
 * A classificação torna-se PÚBLICA apenas após homologação (RN da Fase 1).
 */
final class ClassificacaoService
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /**
     * Calcula e grava posicao_final de todas as participações do evento.
     *
     * CORREÇÃO DE UM BUG REAL: `eliminado_round IS NULL` tem DOIS
     * significados distintos que o código anterior confundia:
     *   (a) o candidato sobreviveu a TODOS os rounds — venceu;
     *   (b) o candidato nunca chegou a soletrar NENHUMA palavra (ficou
     *       marcado "presente" mas nunca foi chamado ao palco) — não
     *       competiu de facto.
     * Os dois casos produzem o MESMO `NULL`, e o comparador tratava-os
     * como equivalentes — um candidato com 0 pontos que nunca soletrou
     * aparecia classificado ACIMA de quem soletrou várias palavras e só
     * foi eliminado a meio. Corrigido: agora conta-se quantas tentativas
     * o candidato teve; sem nenhuma, fica sempre no fim da tabela,
     * independentemente do valor de eliminado_round.
     */
    public function calcular(int $eventoId): array
    {
        $participacoes = $this->db->table('participacoes pa')
            ->select('pa.id, pa.eliminado_round, pa.pontuacao_total,
                      COUNT(t.id) AS tentativas,
                      COALESCE(SUM(t.tempo_resposta_seg), 0) AS tempo_total')
            ->join('tentativas_soletracao t', 't.participacao_id = pa.id', 'left')
            ->where('pa.evento_id', $eventoId)
            ->whereIn('pa.presenca', ['presente'])
            ->groupBy('pa.id')
            ->get()->getResultArray();

        // Ronda "efetiva" para ordenar: quem nunca teve uma única tentativa
        // fica pior do que QUALQUER eliminado real (mesmo no round 1);
        // quem sobreviveu a tudo (e chegou a competir) fica no topo.
        $rondaEfetiva = static function (array $p): int {
            if ((int) $p['tentativas'] === 0) {
                return -1; // nunca competiu: sempre o pior caso
            }

            // O MySQLi devolve TODAS as colunas como string (mesmo as
            // numéricas) salvo configuração especial que este projeto não
            // usa. Sem o cast explícito, um eliminado_round não-nulo
            // (ex.: "4") era devolvido como string — e com
            // declare(strict_types=1) isso é um TypeError contra o
            // ": int" desta função (só null passa incólume pelo ??).
            return $p['eliminado_round'] !== null
                ? (int) $p['eliminado_round']
                : PHP_INT_MAX; // sobreviveu = melhor
        };

        usort($participacoes, static function (array $a, array $b) use ($rondaEfetiva): int {
            return [$rondaEfetiva($b), (int) $b['pontuacao_total'], -(int) $b['tempo_total']]
               <=> [$rondaEfetiva($a), (int) $a['pontuacao_total'], -(int) $a['tempo_total']];
        });

        $posicao = 0;
        $anterior = null;

        foreach ($participacoes as $indice => $p) {
            $chave = [$rondaEfetiva($p), $p['pontuacao_total'], $p['tempo_total']];

            // Empate absoluto partilha a posição (ex-aequo) — sinaliza desempate.
            if ($chave !== $anterior) {
                $posicao = $indice + 1;
            }
            $anterior = $chave;

            $this->db->table('participacoes')->where('id', $p['id'])
                ->update([
                    'posicao_final'    => $posicao,
                    'tempo_total_seg'  => (int) $p['tempo_total'],
                ]);
        }

        return $participacoes;
    }

    /**
     * Homologação pelo coordenador competente: sela os resultados,
     * dispara a progressão e as notificações.
     */
    public function homologar(int $eventoId, int $porUserId): void
    {
        $evento = $this->db->table('eventos_competicao')->where('id', $eventoId)->get()->getRow()
            ?? throw new RuntimeException(lang('Concurso.eventoNaoEncontrado'));

        if ($evento->status !== 'concluido') {
            throw new RuntimeException(lang('Concurso.eventoNaoConcluido'));
        }

        // SALVAGUARDA (bug real corrigido): o estado do evento continua
        // 'concluido' PARA SEMPRE mesmo depois de homologado (não há um
        // estado "homologado" distinto) — sem esta verificação, clicar
        // duas vezes em "Homologar" reenviava as notificações aos
        // encarregados outra vez e duplicava o registo de auditoria
        // (o que, por sua vez, duplicava o evento nas listagens públicas
        // que se apoiam nesse registo para saber o que já foi publicado).
        if ($this->foiHomologado($eventoId)) {
            throw new RuntimeException(lang('Concurso.eventoJaHomologado'));
        }

        // Empates não resolvidos nas posições de qualificação bloqueiam.
        $this->exigirSemEmpatesNasVagas($eventoId);

        // SALVAGUARDA (bug real corrigido): nunca progredir para a fase
        // seguinte quem ficou numa posição de apuramento sem ter tido
        // uma única tentativa — sinal de presença mal marcada ou de
        // classificação desatualizada. Força o coordenador a corrigir
        // ANTES de a progressão contaminar a fase seguinte.
        $this->exigirQualificadosTeremCompetido($eventoId);

        service('auditoria')->registar('homologar_resultados', 'eventos_competicao', $eventoId);

        // Progressão dos qualificados para a fase seguinte.
        service('progressao')->apurarQualificados($eventoId, $porUserId);

        // Resultado passa a público + notificações aos encarregados.
        service('notificador')->notificarResultadosEvento($eventoId);
    }

    /** Já passou pela homologação? (única fonte de verdade: o registo de auditoria). */
    public function foiHomologado(int $eventoId): bool
    {
        return $this->db->table('auditoria_logs')
            ->where('entidade', 'eventos_competicao')
            ->where('entidade_id', $eventoId)
            ->where('acao', 'homologar_resultados')
            ->countAllResults() > 0;
    }

    private function exigirQualificadosTeremCompetido(int $eventoId): void
    {
        $vagas = (int) $this->db->table('eventos_competicao ev')
            ->select('f.vagas_proxima_fase')
            ->join('fases_concurso f', 'f.id = ev.fase_id')
            ->where('ev.id', $eventoId)->get()->getRow()->vagas_proxima_fase ?? 0;

        if ($vagas === 0) {
            return; // fase final: não há progressão a proteger
        }

        $semTentativas = $this->db->table('participacoes pa')
            ->select('pa.id')
            ->where('pa.evento_id', $eventoId)
            ->where('pa.posicao_final <=', $vagas)
            ->where('pa.posicao_final IS NOT NULL')
            ->where('NOT EXISTS (SELECT 1 FROM tentativas_soletracao t WHERE t.participacao_id = pa.id)', null, false)
            ->countAllResults();

        if ($semTentativas > 0) {
            throw new RuntimeException(lang('Concurso.qualificadoSemTentativas'));
        }
    }

    private function exigirSemEmpatesNasVagas(int $eventoId): void
    {
        $vagas = (int) $this->db->table('eventos_competicao ev')
            ->select('f.vagas_proxima_fase')
            ->join('fases_concurso f', 'f.id = ev.fase_id')
            ->where('ev.id', $eventoId)->get()->getRow()->vagas_proxima_fase ?? 0;

        if ($vagas === 0) {
            return; // fase final: não há progressão a proteger
        }

        $duplicados = $this->db->table('participacoes')
            ->select('posicao_final, COUNT(*) AS n')
            ->where('evento_id', $eventoId)
            ->where('posicao_final <=', $vagas)
            ->groupBy('posicao_final')
            ->having('n >', 1)
            ->countAllResults();

        if ($duplicados > 0) {
            throw new RuntimeException(lang('Concurso.empateNasVagas'));
        }
    }
}
