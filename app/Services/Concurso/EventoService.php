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
    /**
     * @return array{confirmados: int, ignorados: int, ignoradosNomes: string[]}
     */
    /**
     * Procura um evento ATIVO (não cancelado) que já ocupe a mesma
     * fase + categoria + escola/província — a combinação que, dentro de
     * uma edição, deve ser única (só pode existir UM evento "Fase
     * Escolar" para a mesma escola e categoria ao mesmo tempo).
     *
     * NOTA: como `fase_id` pertence sempre a UMA edição concreta
     * (fases_concurso.edicao_id), este cruzamento já distingue
     * automaticamente edições diferentes — uma nova edição tem as suas
     * próprias fases, logo nunca colide com eventos de edições
     * anteriores, mesmo que sejam da mesma escola/categoria.
     *
     * Usa o operador NULL-safe `<=>` do MySQL para que escola_id/
     * provincia_id nulos (ex.: fases não-escolares sem escola) sejam
     * comparados corretamente — `NULL = NULL` é falso em SQL normal,
     * mas aqui precisa de contar como "igual".
     */
    public function encontrarDuplicado(
        int $faseId,
        ?int $categoriaId,
        ?int $escolaId,
        ?int $provinciaId,
        ?int $ignorarEventoId = null,
    ): ?object {
        $sql = 'SELECT id, nome, status
                  FROM eventos_competicao
                 WHERE fase_id = ?
                   AND categoria_id  <=> ?
                   AND escola_id     <=> ?
                   AND provincia_id  <=> ?
                   AND status != "cancelado"';
        $params = [$faseId, $categoriaId, $escolaId, $provinciaId];

        if ($ignorarEventoId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $ignorarEventoId;
        }

        return $this->db->query($sql . ' LIMIT 1', $params)->getRow();
    }

    public function confirmarParticipantes(int $eventoId): array
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

        // SALVAGUARDA (lacuna real corrigida): um candidato não pode
        // competir em DOIS eventos da MESMA fase ao mesmo tempo — isso
        // gera duas classificações paralelas e contraditórias para o que
        // devia ser a mesma disputa (ex.: dois eventos "Escolar" criados
        // por engano para a mesma escola/categoria, cada um com o seu
        // palco independente). Excluem-se aqui os candidatos que já têm
        // participação noutro evento ATIVO (não cancelado) desta fase.
        $idsNoutroEvento = $this->db->table('participacoes pa')
            ->select('pa.inscricao_id')
            ->join('eventos_competicao ev2', 'ev2.id = pa.evento_id')
            ->where('ev2.fase_id', $evento->fase_id)
            ->where('pa.evento_id !=', $eventoId)
            ->where('ev2.status !=', 'cancelado')
            ->get()->getResultArray();
        $idsExcluir = array_column($idsNoutroEvento, 'inscricao_id');

        if ($idsExcluir !== []) {
            $builder->whereNotIn('i.id', $idsExcluir);
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

        $nomesIgnorados = [];
        if ($idsExcluir !== []) {
            $nomesIgnorados = array_column(
                $this->db->table('candidatos c')
                    ->select('c.nome_completo')
                    ->join('inscricoes i', 'i.candidato_id = c.id')
                    ->whereIn('i.id', $idsExcluir)
                    ->get()->getResultArray(),
                'nome_completo'
            );
        }

        return [
            'confirmados'    => count($linhas),
            'ignorados'      => count($idsExcluir),
            'ignoradosNomes' => $nomesIgnorados,
        ];
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
