<?php

declare(strict_types=1);

namespace App\Services\Concurso;

use App\Services\Comum\Escopo;
use CodeIgniter\Database\ConnectionInterface;

/**
 * Relatórios do concurso. Consultas de leitura, sempre limitadas ao
 * escopo territorial do utilizador (Fase 4).
 */
final class RelatorioService
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /** Painel por província: escolas, candidatos, inscrições e validações. */
    public function estatisticasProvincias(int $edicaoId, Escopo $escopo): array
    {
        $builder = $this->db->table('provincias p')
            ->select('p.id, p.nome,
                      COUNT(DISTINCT i.escola_id) AS escolas_participantes,
                      COUNT(DISTINCT i.id) AS inscricoes,
                      SUM(i.status = "validada") AS validadas,
                      SUM(i.status = "pendente") AS pendentes,
                      SUM(i.status = "rejeitada") AS rejeitadas')
            ->join('inscricoes i', 'i.provincia_id = p.id AND i.edicao_id = ' . (int) $edicaoId, 'left')
            ->groupBy('p.id, p.nome')
            ->orderBy('p.nome');

        if (! $escopo->eNacional()) {
            $builder->whereIn('p.id', $escopo->provincias ?: [0]);
        }

        return $builder->get()->getResultArray();
    }

    /** Classificação de um evento (para a página de resultados pública). */
    public function classificacaoEvento(int $eventoId): array
    {
        return $this->db->table('participacoes pa')
            ->select('pa.posicao_final, pa.numero_concorrente, pa.pontuacao_total,
                      pa.eliminado_round, pa.tempo_total_seg,
                      c.nome_completo, c.classe_atual, e.nome AS escola')
            ->join('inscricoes i', 'i.id = pa.inscricao_id')
            ->join('candidatos c', 'c.id = i.candidato_id')
            ->join('escolas e', 'e.id = i.escola_id')
            ->where('pa.evento_id', $eventoId)
            ->where('pa.posicao_final IS NOT NULL')
            ->orderBy('pa.posicao_final')
            ->get()->getResultArray();
    }

    /**
     * Percurso round a round de um evento — alimenta a grelha estilo
     * spellingbee.com/round-results: uma linha por candidato, uma célula
     * por round (✓ acertou / ✗ eliminado / − não soletrou).
     */
    public function grelhaRounds(int $eventoId): array
    {
        return $this->db->table('tentativas_soletracao t')
            ->select('pa.id AS participacao_id, c.nome_completo, pa.numero_concorrente,
                      r.numero_round, t.correta, t.apelacao_resultado,
                      p.palavra, t.tempo_resposta_seg')
            ->join('rounds_evento r', 'r.id = t.round_id')
            ->join('participacoes pa', 'pa.id = t.participacao_id')
            ->join('inscricoes i', 'i.id = pa.inscricao_id')
            ->join('candidatos c', 'c.id = i.candidato_id')
            ->join('palavras p', 'p.id = t.palavra_id')
            ->where('r.evento_id', $eventoId)
            ->orderBy('pa.numero_concorrente')->orderBy('r.numero_round')
            ->get()->getResultArray();
    }

    /** Palavras com pior taxa de acerto na edição (valor pedagógico). */
    public function palavrasMaisDificeis(int $edicaoId, int $limite = 20): array
    {
        return $this->db->table('v_historico_uso_palavras')
            ->select('palavra_id, palavra, dificuldade,
                      COUNT(*) AS vezes_usada,
                      ROUND(100 * AVG(correta), 1) AS taxa_acerto')
            ->where('edicao_ano', (int) $this->db->table('edicoes_concurso')
                ->select('ano')->where('id', $edicaoId)->get()->getRow()->ano)
            ->groupBy('palavra_id, palavra, dificuldade')
            ->having('vezes_usada >=', 3)     // amostra mínima para a taxa fazer sentido
            ->orderBy('taxa_acerto', 'ASC')
            ->limit($limite)
            ->get()->getResultArray();
    }
}
