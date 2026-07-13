<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\NoticiaModel;
use CodeIgniter\I18n\Time;
use RuntimeException;

/**
 * Serviço editorial de notícias.
 *
 * Responsabilidades:
 *  - CRUD com sanitização de HTML NA ENTRADA (política da Fase 4);
 *  - slug único com transliteração completa do português;
 *  - transições de estado via MaquinaEstadosNoticia;
 *  - sincronização de categorias e tags (N:N);
 *  - agendamento (o comando cms:publicar-agendados chama este service);
 *  - notificações editoriais (submissão → editores; publicação → autor).
 *
 * As revisões de conteúdo são registadas pelo trigger de BD
 * trg_noticias_revisao (Fase 2) — o service não duplica esse trabalho.
 */
final class NoticiaService
{
    public function __construct(
        private readonly NoticiaModel $model,
        private readonly MaquinaEstadosNoticia $estados,
        private readonly SanitizadorHtml $sanitizador,
    ) {
    }

    // ----------------------------- CRUD -----------------------------

    /** Cria um rascunho. Devolve o ID da notícia. */
    public function criarRascunho(array $dados, int $autorId): int
    {
        $dados = $this->prepararDados($dados);
        $dados['autor_id'] = $autorId;
        $dados['status']   = 'rascunho';
        $dados['slug']     = $this->slugUnico($dados['titulo']);

        $this->model->db->transException(true)->transStart();

        $id = $this->model->insert($dados, true);
        $this->sincronizarTaxonomias($id, $dados);

        $this->model->db->transComplete();

        return $id;
    }

    public function atualizar(int $id, array $dados, int $userId): void
    {
        $noticia = $this->obterOuFalhar($id);

        $dados = $this->prepararDados($dados);
        $dados['editor_id'] = $userId;

        // O slug só é regenerado enquanto NÃO publicada (estabilidade de
        // URLs públicas e SEO: nunca partir links já partilhados).
        if ($noticia->status !== 'publicada'
            && ! empty($dados['titulo'])
            && $dados['titulo'] !== $noticia->titulo) {
            $dados['slug'] = $this->slugUnico($dados['titulo'], $id);
        } else {
            unset($dados['slug']);
        }

        $this->model->db->transException(true)->transStart();

        $this->model->update($id, $dados);
        $this->sincronizarTaxonomias($id, $dados);

        $this->model->db->transComplete();
    }

    // ---------------------- Transições de estado ----------------------

    /**
     * Executa uma transição editorial ('submeter', 'publicar', 'agendar',
     * 'devolver', 'arquivar', 'eliminar', 'restaurar').
     */
    public function transitar(int $id, string $transicao, array $extra = []): void
    {
        $noticia = $this->obterOuFalhar($id);
        $destino = $this->estados->transitar($noticia->status, $transicao);

        $dados = ['status' => $destino, 'editor_id' => auth()->id()];

        if ($destino === 'agendada') {
            // data_agendada chega do formulário em hora local → converter p/ UTC.
            $quando = service('dataHora')->deFormulario($extra['data_agendada'] ?? '');

            if ($quando === null || $quando->getTimestamp() <= time()) {
                throw new RuntimeException(lang('Cms.dataAgendamentoInvalida'));
            }
            $dados['data_agendada'] = $quando->toDateTimeString();
        }

        if ($destino === 'publicada') {
            // Preserva a data original em republicações (SEO e ordenação).
            $dados['data_publicacao'] = $noticia->data_publicacao?->toDateTimeString()
                ?? Time::now('UTC')->toDateTimeString();
            $dados['data_agendada']   = null;
        }

        $this->model->update($id, $dados);
        $this->notificarTransicao($noticia, $transicao, $destino);
    }

    /** Chamado pelo comando cms:publicar-agendados (cron). Devolve nº publicado. */
    public function publicarAgendadasVencidas(): int
    {
        $vencidas = $this->model
            ->where('status', 'agendada')
            ->where('data_agendada <=', Time::now('UTC')->toDateTimeString())
            ->findColumn('id') ?? [];

        foreach ($vencidas as $id) {
            $this->model->update($id, [
                'status'          => 'publicada',
                'data_publicacao' => Time::now('UTC')->toDateTimeString(),
            ]);
        }

        return count($vencidas);
    }

    // --------------------------- Internos ---------------------------

    private function prepararDados(array $dados): array
    {
        // ÚNICO ponto onde HTML rico entra no sistema já limpo.
        if (isset($dados['conteudo'])) {
            $dados['conteudo'] = $this->sanitizador->limpar($dados['conteudo']);

            $palavras = str_word_count(strip_tags($dados['conteudo']));
            $dados['tempo_leitura_min'] = max(1, (int) ceil($palavras / 200));
        }

        return $dados;
    }

    /** Gera slug único; em colisão acrescenta -2, -3... */
    private function slugUnico(string $titulo, ?int $ignorarId = null): string
    {
        helper('texto');
        $base = slug_pt($titulo);
        $slug = $base;
        $n    = 1;

        while ($this->model->slugExiste($slug, $ignorarId)) {
            $slug = $base . '-' . ++$n;
        }

        return $slug;
    }

    private function sincronizarTaxonomias(int $noticiaId, array $dados): void
    {
        if (array_key_exists('categorias', $dados)) {
            $this->model->sincronizarCategorias($noticiaId, (array) $dados['categorias']);
        }
        if (array_key_exists('tags', $dados)) {
            // Tags chegam como nomes livres; o model cria as inexistentes.
            $this->model->sincronizarTags($noticiaId, (array) $dados['tags']);
        }
    }

    private function notificarTransicao(object $noticia, string $transicao, string $destino): void
    {
        // Submissão para revisão → avisa quem pode publicar;
        // publicação/devolução → avisa o autor.
        match (true) {
            $transicao === 'submeter' => service('notificador')->notificarGrupo(
                'editor_noticias',
                'cms_submetida',
                ['titulo' => $noticia->titulo, 'id' => $noticia->id]
            ),
            $destino === 'publicada' => service('notificador')->notificarUtilizador(
                $noticia->autor_id,
                'cms_publicada',
                ['titulo' => $noticia->titulo, 'url' => $noticia->urlPublica()]
            ),
            $transicao === 'devolver' => service('notificador')->notificarUtilizador(
                $noticia->autor_id,
                'cms_devolvida',
                ['titulo' => $noticia->titulo]
            ),
            default => null,
        };
    }

    private function obterOuFalhar(int $id): object
    {
        return $this->model->find($id)
            ?? throw new RuntimeException(lang('Cms.noticiaNaoEncontrada'));
    }
}
