<?php

declare(strict_types=1);

namespace App\Services\Concurso;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;
use RuntimeException;

/**
 * Rounds de um evento: abertura, fecho e estado de sobrevivência.
 */
final class RoundService
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /** Abre o próximo round. Só pode haver UM round em curso por evento. */
    public function abrir(int $eventoId, array $config): int
    {
        $emCurso = $this->db->table('rounds_evento')
            ->where(['evento_id' => $eventoId, 'status' => 'em_curso'])
            ->countAllResults();

        if ($emCurso > 0) {
            throw new RuntimeException(lang('Concurso.jaHaRoundEmCurso'));
        }

        $numero = 1 + (int) $this->db->table('rounds_evento')
            ->where('evento_id', $eventoId)->countAllResults();

        $this->db->table('rounds_evento')->insert([
            'evento_id'          => $eventoId,
            'numero_round'       => $numero,
            'tipo'               => $config['tipo'] ?? 'eliminatorio',
            'dificuldade'        => $config['dificuldade'] ?? 'media',
            'tempo_limite_seg'   => (int) ($config['tempo_limite_seg'] ?? 60),
            'permite_repeticao'  => (int) ($config['permite_repeticao'] ?? 1),
            'permite_definicao'  => (int) ($config['permite_definicao'] ?? 1),
            'permite_etimologia' => (int) ($config['permite_etimologia'] ?? 1),
            'permite_exemplo'    => (int) ($config['permite_exemplo'] ?? 1),
            'iniciado_em'        => Time::now('UTC')->toDateTimeString(),
            'status'             => 'em_curso',
            'created_at'         => utc_agora(),
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Conclui um round, exigindo que todos os sobreviventes tenham
     * soletrado (nenhuma vez pendente).
     */
    public function concluir(int $roundId): array
    {
        $round = $this->db->table('rounds_evento')->where('id', $roundId)->get()->getRow()
            ?? throw new RuntimeException(lang('Concurso.roundNaoEncontrado'));

        $pendentes = $this->db->table('tentativas_soletracao')
            ->where(['round_id' => $roundId, 'correta' => null])
            ->countAllResults();

        if ($pendentes > 0) {
            throw new RuntimeException(lang('Concurso.tentativasPorAvaliar', [$pendentes]));
        }

        $this->db->table('rounds_evento')->where('id', $roundId)->update([
            'status'       => 'concluido',
            'concluido_em' => Time::now('UTC')->toDateTimeString(),
        ]);

        return $this->sobreviventes((int) $round->evento_id);
    }

    /** Participações ainda em prova (presentes e não eliminadas). */
    public function sobreviventes(int $eventoId): array
    {
        return $this->db->table('participacoes pa')
            ->select('pa.id, pa.numero_concorrente, c.nome_completo, pa.pontuacao_total')
            ->join('inscricoes i', 'i.id = pa.inscricao_id')
            ->join('candidatos c', 'c.id = i.candidato_id')
            ->where('pa.evento_id', $eventoId)
            ->where('pa.presenca', 'presente')
            ->where('pa.eliminado_round', null)
            ->orderBy('pa.numero_concorrente')
            ->get()->getResultArray();
    }
}
