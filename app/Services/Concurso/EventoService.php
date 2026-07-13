<?php

declare(strict_types=1);

namespace App\Services\Concurso;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;
use RuntimeException;

/**
 * Gestão de eventos de competição: júri, confirmação de participantes,
 * início e conclusão.
 */
final class EventoService
{
    private const PAPEIS_MINIMOS = ['presidente', 'pronunciador'];

    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /** Atribui um membro do júri (papéis: presidente, jurado, pronunciador, juiz_apelacao, cronometrista, secretario). */
    public function atribuirJuri(int $eventoId, int $userId, string $papel): void
    {
        $this->db->table('juri_evento')->ignore(true)->insert([
            'evento_id'  => $eventoId,
            'user_id'    => $userId,
            'papel'      => $papel,
            'created_at' => utc_agora(),
        ]);

        service('notificador')->notificarUtilizador($userId, 'juri_designado', [
            'evento_id' => $eventoId, 'papel' => $papel,
        ]);
    }

    /**
     * Cria as participações de um evento a partir das inscrições elegíveis:
     *  - fase ESCOLAR: inscrições validadas da escola/categoria do evento;
     *  - fases seguintes: quem tem progressão homologada para esta fase
     *    (progressoes_fase) dentro do território do evento.
     */
    public function confirmarParticipantes(int $eventoId): int
    {
        $evento = $this->obterEvento($eventoId);

        $builder = $this->db->table('inscricoes i')
            ->select('i.id AS inscricao_id')
            ->where('i.status', 'validada')
            ->where('i.categoria_id', $evento->categoria_id);

        if ($evento->tipo_fase === 'escolar') {
            $builder->where('i.escola_id', $evento->escola_id);
        } else {
            // Só entra quem progrediu para ESTA fase.
            $builder->join('progressoes_fase pf', 'pf.inscricao_id = i.id')
                    ->where('pf.fase_destino_id', $evento->fase_id);

            if ($evento->provincia_id !== null) {
                $builder->where('i.provincia_id', $evento->provincia_id);
            }
        }

        $elegiveis = $builder->get()->getResultArray();
        $numero    = 1;
        $linhas    = [];

        foreach ($elegiveis as $e) {
            $linhas[] = [
                'evento_id'          => $eventoId,
                'inscricao_id'       => $e['inscricao_id'],
                'numero_concorrente' => str_pad((string) $numero++, 3, '0', STR_PAD_LEFT),
                'presenca'           => 'confirmada',
                'created_at'         => utc_agora(),
            ];
        }

        if ($linhas !== []) {
            // ignore(true): reexecutar a confirmação não duplica (UNIQUE evento+inscrição).
            $this->db->table('participacoes')->ignore(true)->insertBatch($linhas);
        }

        return count($linhas);
    }

    /** Check-in no dia do evento. */
    public function registarPresenca(int $participacaoId, string $presenca): void
    {
        if (! in_array($presenca, ['presente', 'ausente', 'desistiu'], true)) {
            throw new RuntimeException('Estado de presença inválido.');
        }

        $this->db->table('participacoes')->where('id', $participacaoId)
            ->update(['presenca' => $presenca, 'updated_at' => utc_agora()]);
    }

    /** Valida pré-condições e coloca o evento em curso. */
    public function iniciar(int $eventoId): void
    {
        $evento = $this->obterEvento($eventoId);

        if ($evento->status !== 'agendado') {
            throw new RuntimeException(lang('Concurso.eventoNaoAgendado'));
        }

        // Júri mínimo: presidente + pronunciador.
        foreach (self::PAPEIS_MINIMOS as $papel) {
            $tem = $this->db->table('juri_evento')
                ->where(['evento_id' => $eventoId, 'papel' => $papel])
                ->countAllResults() > 0;

            if (! $tem) {
                throw new RuntimeException(lang('Concurso.juriIncompleto', [$papel]));
            }
        }

        // Pool com palavras disponíveis.
        if (array_sum(service('palavras')->restantesNoPool($eventoId)) === 0) {
            throw new RuntimeException(lang('Concurso.poolVazio'));
        }

        // Pelo menos 2 presentes.
        $presentes = $this->db->table('participacoes')
            ->where(['evento_id' => $eventoId, 'presenca' => 'presente'])
            ->countAllResults();

        if ($presentes < 2) {
            throw new RuntimeException(lang('Concurso.participantesInsuficientes'));
        }

        $this->db->table('eventos_competicao')->where('id', $eventoId)
            ->update(['status' => 'em_curso', 'updated_at' => utc_agora()]);
    }

    public function concluir(int $eventoId): void
    {
        // A conclusão calcula a classificação provisória (homologação é passo à parte).
        service('classificacao')->calcular($eventoId);

        $this->db->table('eventos_competicao')->where('id', $eventoId)
            ->update(['status' => 'concluido', 'updated_at' => utc_agora()]);
    }

    private function obterEvento(int $id): object
    {
        return $this->db->table('eventos_competicao ev')
            ->select('ev.*, f.tipo_fase, f.edicao_id')
            ->join('fases_concurso f', 'f.id = ev.fase_id')
            ->where('ev.id', $id)->get()->getRow()
            ?? throw new RuntimeException(lang('Concurso.eventoNaoEncontrado'));
    }
}
