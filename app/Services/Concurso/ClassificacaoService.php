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

    /** Calcula e grava posicao_final de todas as participações do evento. */
    public function calcular(int $eventoId): array
    {
        $participacoes = $this->db->table('participacoes pa')
            ->select('pa.id, pa.eliminado_round, pa.pontuacao_total,
                      COALESCE(SUM(t.tempo_resposta_seg), 0) AS tempo_total')
            ->join('tentativas_soletracao t', 't.participacao_id = pa.id', 'left')
            ->where('pa.evento_id', $eventoId)
            ->whereIn('pa.presenca', ['presente'])
            ->groupBy('pa.id')
            ->get()->getResultArray();

        usort($participacoes, static function (array $a, array $b): int {
            // NULL em eliminado_round = sobreviveu até ao fim → melhor.
            $rA = $a['eliminado_round'] ?? PHP_INT_MAX;
            $rB = $b['eliminado_round'] ?? PHP_INT_MAX;

            return [$rB, (int) $b['pontuacao_total'], -(int) $b['tempo_total']]
               <=> [$rA, (int) $a['pontuacao_total'], -(int) $a['tempo_total']];
        });

        $posicao = 0;
        $anterior = null;

        foreach ($participacoes as $indice => $p) {
            $chave = [$p['eliminado_round'], $p['pontuacao_total'], $p['tempo_total']];

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

        // Empates não resolvidos nas posições de qualificação bloqueiam.
        $this->exigirSemEmpatesNasVagas($eventoId);

        service('auditoria')->registar('homologar_resultados', 'eventos_competicao', $eventoId);

        // Progressão dos qualificados para a fase seguinte.
        service('progressao')->apurarQualificados($eventoId, $porUserId);

        // Resultado passa a público + notificações aos encarregados.
        service('notificador')->notificarResultadosEvento($eventoId);
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
