<?php

namespace App\Services\Concurso;

use CodeIgniter\Shield\Entities\User;
use App\Models\InscricaoModel;
use App\Services\Comum\Escopo;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;
use RuntimeException;

/**
 * Serviço de inscrições — guardião das regras RN-01, RN-02, RN-03 e RN-05.
 *
 * A inscrição completa (candidato + encarregado + inscrição) é UMA
 * transação: ou entra tudo, ou não entra nada.
 */
final class InscricaoService
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly InscricaoModel $inscricoes,
    ) {
    }

    /**
     * Inscreve um novo candidato numa edição.
     *
     * @param array $candidato   Dados do candidato (validados pelo controller)
     * @param array $encarregado Dados do encarregado principal
     * @return array ['inscricao_id' => int, 'numero_inscricao' => string, 'uuid' => string]
     * @throws RuntimeException Com mensagem traduzível quando alguma regra falha
     */
    public function inscrever(array $candidato, array $encarregado, int $edicaoId, int $categoriaId): array
    {
        $edicao = $this->obterEdicao($edicaoId);
        $categoria = $this->obterCategoria($categoriaId, $edicaoId);
        $escola = $this->obterEscola((int) $candidato['escola_id']);

        // ---- Prazo de inscrições (datas da edição, comparadas em UTC) ----
        $agora = Time::now('UTC');
        if (
            !service('dataHora')->dentroDoPrazo(
                $edicao->data_abertura_inscricoes,
                $edicao->data_encerramento_inscricoes,
                $agora
            )
        ) {
            throw new RuntimeException(lang('Concurso.inscricoesFechadas'));
        }

        // ---- RN-03: classe e idade compatíveis com a categoria ----
        if (
            (int) $candidato['classe_atual'] < $categoria->classe_minima
            || (int) $candidato['classe_atual'] > $categoria->classe_maxima
        ) {
            throw new RuntimeException(lang('Concurso.classeForaDaCategoria'));
        }

        $referencia = Time::parse($edicao->data_inicio ?? $agora->toDateString(), 'UTC');
        $idade = Time::parse($candidato['data_nascimento'], 'UTC')
            ->difference($referencia)->getYears();

        if (
            ($categoria->idade_minima !== null && $idade < $categoria->idade_minima)
            || ($categoria->idade_maxima !== null && $idade > $categoria->idade_maxima)
        ) {
            throw new RuntimeException(lang('Concurso.idadeForaDaCategoria'));
        }

        // ---- RN-05: encarregado com autorização assinada ----
        if (empty($encarregado['autorizou'])) {
            throw new RuntimeException(lang('Concurso.faltaAutorizacaoEncarregado'));
        }

        // ---- RN-01 por construção: a província É a da escola ----
        // O formulário nem envia provincia_id: deriva-se da escola escolhida,
        // eliminando a possibilidade de inconsistência na origem.
        $provinciaId = (int) $escola->provincia_id;

        $this->db->transException(true)->transStart();

        // O número é gerado ANTES do candidato: é ele que o guarda
        // (coluna `numero_inscricao` vive em `candidatos`, não em `inscricoes`).
        $numero = $this->gerarNumeroInscricao($edicao, $provinciaId);
        $candidatoId = $this->criarCandidato($candidato, $escola, $provinciaId, $numero);

        // RN-02: uma inscrição por edição (a UNIQUE do BD é a rede final;
        // verificamos antes para devolver mensagem amigável).
        $jaExiste = $this->db->table('inscricoes')
            ->where(['candidato_id' => $candidatoId, 'edicao_id' => $edicaoId])
            ->countAllResults() > 0;

        if ($jaExiste) {
            throw new RuntimeException(lang('Concurso.jaInscritoNestaEdicao'));
        }

        $this->criarEncarregado($candidatoId, $encarregado);

        $uuid = service('uuid')->v4();

        $inscricaoId = $this->inscricoes->insert([
            'uuid' => $uuid,
            'candidato_id' => $candidatoId,
            'edicao_id' => $edicaoId,
            'categoria_id' => $categoriaId,
            'provincia_id' => $provinciaId,
            'escola_id' => $escola->id,
            'data_inscricao' => utc_agora(),
            'status' => 'pendente',
            'observacoes' => $candidato['notas'] ?? null,
            'created_at' => utc_agora(),
        ], true);

        $this->db->transComplete();

        // Notificação fora da transação (a falha de um SMS não desfaz a inscrição).
        service('notificador')->notificar('inscricao_recebida', [
            'email' => $encarregado['email'] ?? null,
            'telefone' => $encarregado['telefone'],
        ], [
            'candidato_nome' => $candidato['nome_completo'],
            'encarregado_nome' => $encarregado['nome_completo'],
            'numero_inscricao' => $numero,
            'edicao_nome' => $edicao->nome,
            'link_acompanhamento' => site_url('inscricao/estado/'
                . service('urlCrypt')->cifrarLinkExterno($inscricaoId, 'comprovativo')),
        ]);

        return ['inscricao_id' => $inscricaoId, 'numero_inscricao' => $numero, 'uuid' => $uuid];
    }

    /** Validação por coordenador (com verificação de escopo — Fase 4). */
    public function validar(int $inscricaoId, Escopo $escopo, int $porUserId): void
    {
        $inscricao = $this->obterInscricao($inscricaoId);
        service('autorizacao')->exigirEscopo($escopo, $inscricao);

        if ($inscricao->status !== 'pendente') {
            throw new RuntimeException(lang('Concurso.inscricaoNaoPendente'));
        }

        $this->inscricoes->update($inscricaoId, [
            'status' => 'validada',
            'validada_por' => $porUserId,
            'data_validacao' => utc_agora(),
        ]);

        $this->notificarEncarregado($inscricao, 'inscricao_validada');
    }

    public function rejeitar(int $inscricaoId, Escopo $escopo, string $motivo, int $porUserId): void
    {
        $inscricao = $this->obterInscricao($inscricaoId);
        service('autorizacao')->exigirEscopo($escopo, $inscricao);

        $this->inscricoes->update($inscricaoId, [
            'status' => 'rejeitada',
            'motivo_rejeicao' => $motivo,
            'validada_por' => $porUserId,
            'data_validacao' => utc_agora(),
        ]);

        $this->notificarEncarregado($inscricao, 'inscricao_rejeitada', ['motivo' => $motivo]);
    }

    // ------------------------------ Internos ------------------------------

    /**
     * Número de inscrição sequencial por edição+província: 2026-LDA-00042.
     *
     * Concorrência: GET_LOCK nomeado serializa apenas quem gera número da
     * MESMA edição+província (decisão da Fase 1: nada de MAX()+1 sem lock).
     */
    private function gerarNumeroInscricao(object $edicao, int $provinciaId): string
    {
        $provincia = $this->db->table('provincias')->where('id', $provinciaId)->get()->getRow();
        $chaveLock = "numinsc_{$edicao->id}_{$provinciaId}";

        $obteve = $this->db->query('SELECT GET_LOCK(?, 5) AS l', [$chaveLock])->getRow()->l;
        if ((int) $obteve !== 1) {
            throw new RuntimeException(lang('Concurso.tenteNovamente'));
        }

        try {
            $ultimo = $this->db->table('inscricoes')
                ->selectMax('id')  // apenas para contar dentro do lock
                ->where(['edicao_id' => $edicao->id, 'provincia_id' => $provinciaId])
                ->countAllResults();

            $seq = str_pad((string) ($ultimo + 1), 5, '0', STR_PAD_LEFT);

            return "{$edicao->ano}-{$provincia->codigo}-{$seq}";
        } finally {
            $this->db->query('SELECT RELEASE_LOCK(?)', [$chaveLock]);
        }
    }

    private function criarCandidato(array $dados, object $escola, int $provinciaId, string $numero): int
    {
        $this->db->table('candidatos')->insert([
            'uuid' => service('uuid')->v4(),
            'user_id' => /*$this->obterOuCriarUsuario($dados) ??*/ null,
            'numero_inscricao' => $numero,
            'nome_completo' => $dados['nome_completo'],
            'nome_preferido' => $dados['nome_preferido'] ?? null,
            'genero' => $dados['genero'],
            'data_nascimento' => $dados['data_nascimento'],
            'bi_numero' => $dados['bi_numero'] ?? null,
            'cedula_numero' => $dados['cedula_numero'] ?? null,
            'escola_id' => $escola->id,
            'provincia_id' => $provinciaId,
            'municipio_id' => $escola->municipio_id,
            'classe_atual' => (int) $dados['classe_atual'],
            'turma' => $dados['turma'] ?? null,
            'endereco' => $dados['endereco'] ?? null,
            'telefone_contacto' => $dados['telefone_contacto'] ?? null,
            'email_contacto' => $dados['email_contacto'] ?? null,
            'tem_necessidades_especiais' => (int) ($dados['tem_necessidades_especiais'] ?? 0),
            'descricao_necessidades' => $dados['descricao_necessidades'] ?? null,
            'idioma_materno' => $dados['idioma_materno'] ?? null,
            'outros_idiomas' => $dados['outros_idiomas'] ?? null,
            'notas' => $dados['notas'] ?? null,
            'ativo' => 1,
            'created_at' => utc_agora(),
        ]);

        return (int) $this->db->insertID();
    }

    private function criarEncarregado(int $candidatoId, array $dados): void
    {
        $this->db->table('encarregados_educacao')->insert([
            'user_id' => /*$this->obterOuCriarUsuario($dados) ??*/ null,
            'candidato_id' => $candidatoId,
            'nome_completo' => $dados['nome_completo'],
            'parentesco' => $dados['parentesco'],
            'bi_numero' => $dados['bi_numero'] ?? null,
            'telefone' => $dados['telefone'],
            'telefone_alt' => $dados['telefone_alt'] ?? null,
            'email' => $dados['email'] ?? null,
            'endereco' => $dados['endereco'] ?? null,
            'profissao' => $dados['profissao'] ?? null,
            'principal' => 1,
            'autorizou' => 1,
            'data_autorizacao' => utc_agora(),
            'created_at' => utc_agora(),
        ]);
    }


    private function obterOuCriarUsuario(array $dados): int
    {
        $users = auth()->getProvider();
        $user = $users->findByCredentials(['email' => $dados['email'] ?? null]);

        if ($user !== null && $user->active) {
            return $user->id ?? null;
        }


        $senhaTemporaria = bin2hex(random_bytes(8));

        $novoUser = new User([
            'username' => $dados['email'],
            'email' => $dados['email'],
            'password' => $senhaTemporaria,
        ]);

        $users->save($novoUser);

       
        $novoUser = $users->findById($users->getInsertID());
        $novoUser->activate();
        $users->addToDefaultGroup($novoUser);

        return $novoUser->id ?? null; // Retorna o id do novo usuário ou null se algo deu errado
    }



    private function notificarEncarregado(object $inscricao, string $evento, array $extra = []): void
    {
        $enc = $this->db->table('encarregados_educacao')
            ->where(['candidato_id' => $inscricao->candidato_id, 'principal' => 1])
            ->get()->getRow();

        if ($enc === null) {
            return;
        }

        service('notificador')->notificar(
            $evento,
            ['email' => $enc->email, 'telefone' => $enc->telefone],
            $extra + [
                'candidato_nome' => $inscricao->nome_completo ?? '',
                'numero_inscricao' => $inscricao->numero_inscricao ?? '',
                'provincia' => $inscricao->provincia ?? '',
            ]
        );
    }

    private function obterEdicao(int $id): object
    {
        return $this->db->table('edicoes_concurso')->where('id', $id)->get()->getRow()
            ?? throw new RuntimeException(lang('Concurso.edicaoNaoEncontrada'));
    }

    private function obterCategoria(int $id, int $edicaoId): object
    {
        return $this->db->table('categorias_competicao')
            ->where(['id' => $id, 'edicao_id' => $edicaoId])->get()->getRow()
            ?? throw new RuntimeException(lang('Concurso.categoriaInvalida'));
    }

    private function obterEscola(int $id): object
    {
        return $this->db->table('escolas')->where(['id' => $id, 'ativo' => 1])->get()->getRow()
            ?? throw new RuntimeException(lang('Concurso.escolaInvalida'));
    }

    private function obterInscricao(int $id): object
    {
        return $this->inscricoes->comCandidatoEEscola()->find($id)
            ?? throw new RuntimeException(lang('Concurso.inscricaoNaoEncontrada'));
    }
}
