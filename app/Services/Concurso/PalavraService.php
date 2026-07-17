<?php

declare(strict_types=1);

namespace App\Services\Concurso;

use CodeIgniter\Database\ConnectionInterface;
use RuntimeException;

/**
 * Banco de palavras: montagem do pool de um evento e entrega de
 * palavras durante os rounds.
 *
 * RN-06: só palavras VALIDADAS entram em pool; a marcação de "usada"
 * é feita pelo trigger trg_tentativa_estatistica_palavra (Fase 2).
 */
final class PalavraService
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /**
     * Monta (ou reforça) o pool de um evento com N palavras por nível de
     * dificuldade, adequadas ao intervalo de classes da categoria e ainda
     * não usadas nesta EDIÇÃO (evita repetir palavras entre eventos da
     * mesma edição — candidatos podem assistir aos eventos uns dos outros).
     */
    public function montarPool(int $eventoId, array $quantidadesPorDificuldade): int
    {
        $evento = $this->obterEventoComContexto($eventoId);
        $total  = 0;

        foreach ($quantidadesPorDificuldade as $dificuldade => $quantidade) {
            $candidatas = $this->db->table('palavras p')
                ->select('p.id')
                ->where('p.validada', 1)
                ->where('p.deleted_at', null)
                ->where('p.dificuldade', $dificuldade)
                ->where('p.nivel_minimo_classe <=', $evento->classe_minima)
                ->where('p.nivel_maximo_classe >=', $evento->classe_maxima)
                // Ainda não usadas nesta edição:
                ->whereNotIn('p.id', static function ($sub) use ($evento) {
                    return $sub->select('t.palavra_id')
                        ->from('tentativas_soletracao t')
                        ->join('rounds_evento r', 'r.id = t.round_id')
                        ->join('eventos_competicao ev', 'ev.id = r.evento_id')
                        ->join('fases_concurso f', 'f.id = ev.fase_id')
                        ->where('f.edicao_id', $evento->edicao_id);
                })
                // Nem já no pool deste evento:
                ->whereNotIn('p.id', static function ($sub) use ($eventoId) {
                    return $sub->select('palavra_id')->from('pool_palavras_evento')
                        ->where('evento_id', $eventoId);
                })
                ->orderBy('RAND()')   // sorteio no SGBD; volumes de pool são pequenos
                ->limit((int) $quantidade)
                ->get()->getResultArray();

            if (count($candidatas) < $quantidade) {
                // Mensagem DIAGNÓSTICA: explica porque faltam palavras.
                throw new RuntimeException($this->explicarFalta(
                    $evento, $dificuldade, (int) $quantidade, count($candidatas)
                ));
            }

            $linhas = array_map(static fn ($p) => [
                'evento_id'  => $eventoId,
                'palavra_id' => $p['id'],
                'usada'      => 0,
                'created_at' => utc_agora(),
            ], $candidatas);

            $this->db->table('pool_palavras_evento')->insertBatch($linhas);
            $total += count($linhas);
        }

        return $total;
    }

    /**
     * Sorteia a próxima palavra NÃO usada do pool com a dificuldade do
     * round. Devolve a palavra completa (definição, silabação, exemplo,
     * etimologia, notas do pronunciador...) ou lança se o pool esgotou.
     */
    public function proximaDoPool(int $eventoId, string $dificuldade): object
    {
        return $this->db->table('pool_palavras_evento ppe')
            ->select('p.*')
            ->join('palavras p', 'p.id = ppe.palavra_id')
            ->where('ppe.evento_id', $eventoId)
            ->where('ppe.usada', 0)
            ->where('p.dificuldade', $dificuldade)
            ->orderBy('RAND()')
            ->limit(1)
            ->get()->getRow()
            ?? throw new RuntimeException(lang('Concurso.poolEsgotado', [$dificuldade]));
    }

    /** Palavras restantes no pool, por dificuldade (painel do júri). */
    public function restantesNoPool(int $eventoId): array
    {
        $linhas = $this->db->table('pool_palavras_evento ppe')
            ->select('p.dificuldade, COUNT(*) AS total')
            ->join('palavras p', 'p.id = ppe.palavra_id')
            ->where('ppe.evento_id', $eventoId)
            ->where('ppe.usada', 0)
            ->groupBy('p.dificuldade')
            ->get()->getResultArray();

        return array_column($linhas, 'total', 'dificuldade');
    }

    /**
     * Explica ao coordenador PORQUE faltam palavras, em vez de dizer
     * apenas "disponíveis 0". A causa mais comum é palavras por VALIDAR.
     */
    private function explicarFalta(object $evento, string $dificuldade, int $pedidas, int $disponiveis): string
    {
        $total = $this->db->table('palavras')
            ->where('dificuldade', $dificuldade)
            ->where('deleted_at', null)
            ->countAllResults();

        $porValidar = $this->db->table('palavras')
            ->where('dificuldade', $dificuldade)
            ->where('validada', 0)
            ->where('deleted_at', null)
            ->countAllResults();

        $foraDeClasse = $this->db->table('palavras')
            ->where('dificuldade', $dificuldade)
            ->where('validada', 1)
            ->where('deleted_at', null)
            ->groupStart()
                ->where('nivel_minimo_classe >', $evento->classe_minima)
                ->orWhere('nivel_maximo_classe <', $evento->classe_maxima)
            ->groupEnd()
            ->countAllResults();

        $msg = sprintf(
            'Faltam palavras de dificuldade "%s": pediu %d, disponíveis %d.',
            $dificuldade, $pedidas, $disponiveis
        );

        if ($total === 0) {
            return $msg . ' Não existe nenhuma palavra com esta dificuldade no banco.';
        }

        $causas = [];

        if ($porValidar > 0) {
            $causas[] = sprintf(
                '%d palavra(s) existe(m) mas ainda NÃO foram VALIDADAS '
                . '(Banco de palavras → botão "Validar")',
                $porValidar
            );
        }

        if ($foraDeClasse > 0) {
            $causas[] = sprintf(
                '%d palavra(s) validada(s) está(ão) fora do intervalo de classes '
                . 'desta categoria (%d.ª a %d.ª)',
                $foraDeClasse, $evento->classe_minima, $evento->classe_maxima
            );
        }

        if ($causas === []) {
            $causas[] = 'as restantes já foram usadas noutro evento desta edição '
                . '(uma palavra não se repete na mesma edição)';
        }

        return $msg . ' Motivo: ' . implode('; ', $causas) . '.';
    }

    /**
     * Devolve ao conjunto uma palavra que foi soletrada INCORRETAMENTE.
     *
     * Uso de negócio: numa palavra falhada, é comum poder reaproveitá-la
     * mais tarde no MESMO evento (outro round) — desde que ninguém a
     * tenha acertado nem tenha havido apelação aceite. Nunca se devolve
     * uma palavra que foi corretamente soletrada.
     */
    public function devolverAoPool(int $eventoId, int $palavraId): void
    {
        $tentativa = $this->db->table('tentativas_soletracao t')
            ->select('t.correta, t.apelacao_resultado')
            ->join('rounds_evento r', 'r.id = t.round_id')
            ->where('r.evento_id', $eventoId)
            ->where('t.palavra_id', $palavraId)
            ->orderBy('t.id', 'DESC')
            ->get()->getRow();

        if ($tentativa === null) {
            throw new RuntimeException(lang('Concurso.palavraNaoUsadaNoEvento'));
        }

        if ((int) $tentativa->correta === 1 || $tentativa->apelacao_resultado === 'aceite') {
            throw new RuntimeException(lang('Concurso.palavraAcertadaNaoDevolve'));
        }

        $this->db->table('pool_palavras_evento')
            ->where(['evento_id' => $eventoId, 'palavra_id' => $palavraId])
            ->update(['usada' => 0]);
    }

    /** Acrescenta palavras ESPECÍFICAS ao conjunto (escolha manual). */
    public function adicionarAoPool(int $eventoId, array $palavraIds): int
    {
        $linhas = [];

        foreach (array_unique(array_map('intval', $palavraIds)) as $id) {
            $jaEsta = $this->db->table('pool_palavras_evento')
                ->where(['evento_id' => $eventoId, 'palavra_id' => $id])
                ->countAllResults() > 0;

            if ($jaEsta) {
                continue;
            }

            $linhas[] = [
                'evento_id'  => $eventoId,
                'palavra_id' => $id,
                'usada'      => 0,
                'created_at' => utc_agora(),
            ];
        }

        if ($linhas !== []) {
            $this->db->table('pool_palavras_evento')->insertBatch($linhas);
        }

        return count($linhas);
    }

    /** Palavras validadas e elegíveis para o evento (para escolha manual). */
    public function elegiveisParaEvento(int $eventoId, string $termo = ''): array
    {
        $evento = $this->obterEventoComContexto($eventoId);

        $q = $this->db->table('palavras p')
            ->select('p.id, p.palavra, p.dificuldade, p.silabacao')
            ->where('p.validada', 1)
            ->where('p.deleted_at', null)
            ->where('p.nivel_minimo_classe <=', $evento->classe_minima)
            ->where('p.nivel_maximo_classe >=', $evento->classe_maxima)
            ->whereNotIn('p.id', static function ($sub) use ($eventoId) {
                return $sub->select('palavra_id')->from('pool_palavras_evento')
                    ->where('evento_id', $eventoId);
            });

        if ($termo !== '') {
            $q->like('p.palavra', $termo);
        }

        return $q->orderBy('p.palavra')->limit(100)->get()->getResult();
    }

    private function obterEventoComContexto(int $eventoId): object
    {
        return $this->db->table('eventos_competicao ev')
            ->select('ev.*, f.edicao_id, cat.classe_minima, cat.classe_maxima')
            ->join('fases_concurso f', 'f.id = ev.fase_id')
            ->join('categorias_competicao cat', 'cat.id = ev.categoria_id')
            ->where('ev.id', $eventoId)
            ->get()->getRow()
            ?? throw new RuntimeException(lang('Concurso.eventoNaoEncontrado'));
    }
}
