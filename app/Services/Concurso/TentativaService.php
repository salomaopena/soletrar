<?php

declare(strict_types=1);

namespace App\Services\Concurso;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;
use RuntimeException;

/**
 * Execução ao vivo do concurso: entrega de palavras, registo de
 * respostas, avaliação do júri e apelações (RN-07).
 *
 * Nada é apagado: correções acontecem por apelação, com autor e motivo
 * registados. A tabela tentativas_soletracao é o histórico oficial.
 */
final class TentativaService
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly PalavraService $palavras,
    ) {
    }

    /**
     * Inicia a vez de um candidato: sorteia palavra do pool e cria a
     * tentativa. Devolve [tentativa_id, palavra(objeto completo)].
     */
    public function iniciarVez(int $roundId, int $participacaoId, int $pronunciadorId): array
    {
        $round = $this->obterRound($roundId);

        if ($round->status !== 'em_curso') {
            throw new RuntimeException(lang('Concurso.roundNaoEstaEmCurso'));
        }

        $this->exigirParticipanteAtivo($participacaoId, $round);

        $palavra = $this->palavras->proximaDoPool((int) $round->evento_id, $round->dificuldade);

        $ordem = 1 + (int) $this->db->table('tentativas_soletracao')
            ->where('round_id', $roundId)->countAllResults();

        $this->db->table('tentativas_soletracao')->insert([
            'round_id'        => $roundId,
            'participacao_id' => $participacaoId,
            'palavra_id'      => $palavra->id,
            'ordem_no_round'  => $ordem,
            'pronunciador_id' => $pronunciadorId,
            'iniciado_em'     => Time::now('UTC')->toDateTimeString(),
        ]);
        // NOTA: o trigger trg_tentativa_estatistica_palavra marca a palavra
        // como usada no pool e incrementa a estatística global.

        return ['tentativa_id' => (int) $this->db->insertID(), 'palavra' => $palavra];
    }

    /** Regista pedidos do candidato (repetição/definição/etimologia/exemplo). */
    public function registarPedido(int $tentativaId, string $pedido): void
    {
        $coluna = match ($pedido) {
            'repeticao'  => 'pediu_repeticao',
            'definicao'  => 'pediu_definicao',
            'etimologia' => 'pediu_etimologia',
            'exemplo'    => 'pediu_exemplo',
            default      => throw new RuntimeException('Pedido desconhecido.'),
        };

        // O tipo de pedido tem de estar permitido na configuração do round.
        $tentativa = $this->obterTentativa($tentativaId);
        $round     = $this->obterRound((int) $tentativa->round_id);
        $flagRound = 'permite_' . ($pedido === 'repeticao' ? 'repeticao' : $pedido);

        if (! (int) $round->{$flagRound}) {
            throw new RuntimeException(lang('Concurso.pedidoNaoPermitidoNesteRound'));
        }

        $this->db->table('tentativas_soletracao')
            ->where('id', $tentativaId)->update([$coluna => 1]);
    }

    /**
     * Regista a soletração dada e a decisão do juiz numa só operação
     * (fluxo normal de palco: o juiz decide imediatamente).
     * A comparação automática com palavra_normalizada é apresentada ao
     * juiz como SUGESTÃO — a decisão humana prevalece sempre.
     */
    public function avaliar(int $tentativaId, string $respostaDada, bool $correta, int $juizId): void
    {
        $tentativa = $this->obterTentativa($tentativaId);

        if ($tentativa->correta !== null) {
            throw new RuntimeException(lang('Concurso.tentativaJaAvaliada'));
        }

        $agora  = Time::now('UTC');
        $inicio = Time::parse($tentativa->iniciado_em, 'UTC');
        $round  = $this->obterRound((int) $tentativa->round_id);

        $this->db->transException(true)->transStart();

        $this->db->table('tentativas_soletracao')->where('id', $tentativaId)->update([
            'resposta_dada'      => mb_strtolower(trim($respostaDada)),
            'correta'            => (int) $correta,
            'tempo_resposta_seg' => max(0, $agora->getTimestamp() - $inicio->getTimestamp()),
            'juiz_decisao_id'    => $juizId,
            'pontos_atribuidos'  => $correta ? $this->pontosPorDificuldade($round->dificuldade) : 0,
            'respondido_em'      => $agora->toDateTimeString(),
        ]);

        if ($correta) {
            $this->db->table('participacoes')
                ->where('id', $tentativa->participacao_id)
                ->set('pontuacao_total', 'pontuacao_total + ' . $this->pontosPorDificuldade($round->dificuldade), false)
                ->update();
        } elseif ($round->tipo === 'eliminatorio' || $round->tipo === 'final') {
            // Errou em round eliminatório → eliminado neste round.
            $this->db->table('participacoes')
                ->where('id', $tentativa->participacao_id)
                ->update(['eliminado_round' => $round->numero_round]);
        }

        $this->db->transComplete();
    }

    /** Abre uma apelação sobre uma tentativa avaliada. */
    public function solicitarApelacao(int $tentativaId, string $motivo): void
    {
        $this->db->table('tentativas_soletracao')->where('id', $tentativaId)->update([
            'apelacao_solicitada' => 1,
            'apelacao_resultado'  => 'pendente',
            'apelacao_motivo'     => $motivo,
        ]);
    }

    /**
     * Decisão do juiz de apelação. Se ACEITE sobre uma tentativa errada,
     * reverte a eliminação e credita os pontos — tudo rastreado.
     */
    public function decidirApelacao(int $tentativaId, bool $aceite, int $juizApelacaoId): void
    {
        $tentativa = $this->obterTentativa($tentativaId);

        if ($tentativa->apelacao_resultado !== 'pendente') {
            throw new RuntimeException(lang('Concurso.apelacaoJaDecidida'));
        }

        $this->db->transException(true)->transStart();

        $this->db->table('tentativas_soletracao')->where('id', $tentativaId)->update([
            'apelacao_resultado' => $aceite ? 'aceite' : 'rejeitada',
            'juiz_decisao_id'    => $juizApelacaoId,
        ]);

        if ($aceite && ! (int) $tentativa->correta) {
            $round  = $this->obterRound((int) $tentativa->round_id);
            $pontos = $this->pontosPorDificuldade($round->dificuldade);

            $this->db->table('tentativas_soletracao')->where('id', $tentativaId)->update([
                'correta'           => 1,
                'pontos_atribuidos' => $pontos,
            ]);

            $this->db->table('participacoes')
                ->where('id', $tentativa->participacao_id)
                ->where('eliminado_round', $round->numero_round)   // só reverte SE foi este round a eliminar
                ->update(['eliminado_round' => null]);

            $this->db->table('participacoes')
                ->where('id', $tentativa->participacao_id)
                ->set('pontuacao_total', "pontuacao_total + {$pontos}", false)
                ->update();
        }

        $this->db->transComplete();
    }

    // ------------------------------ Internos ------------------------------

    /** Pontuação por dificuldade — lida das configurações, com defaults. */
    private function pontosPorDificuldade(string $dificuldade): int
    {
        $mapa = service('configuracao')->obter('pontos_por_dificuldade', [
            'muito_facil' => 1, 'facil' => 2, 'media' => 3, 'dificil' => 4, 'muito_dificil' => 5,
        ]);

        return (int) ($mapa[$dificuldade] ?? 1);
    }

    private function exigirParticipanteAtivo(int $participacaoId, object $round): void
    {
        $p = $this->db->table('participacoes')
            ->where(['id' => $participacaoId, 'evento_id' => $round->evento_id])
            ->get()->getRow()
            ?? throw new RuntimeException(lang('Concurso.participacaoInvalida'));

        if ($p->presenca !== 'presente') {
            throw new RuntimeException(lang('Concurso.candidatoNaoPresente'));
        }
        if ($p->eliminado_round !== null) {
            throw new RuntimeException(lang('Concurso.candidatoJaEliminado'));
        }
    }

    private function obterRound(int $id): object
    {
        return $this->db->table('rounds_evento')->where('id', $id)->get()->getRow()
            ?? throw new RuntimeException(lang('Concurso.roundNaoEncontrado'));
    }

    private function obterTentativa(int $id): object
    {
        return $this->db->table('tentativas_soletracao')->where('id', $id)->get()->getRow()
            ?? throw new RuntimeException(lang('Concurso.tentativaNaoEncontrada'));
    }
}
