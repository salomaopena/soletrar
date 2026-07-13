<?php
/**
 * Formulário editorial de notícia (criar/editar).
 * Dados: $noticia (null se nova), $categorias, $transicoes (só em edição).
 *
 * Os botões de ação são gerados pela MÁQUINA DE ESTADOS (Fase 5): a
 * interface nunca mostra transições que o utilizador não pode executar.
 */
$eNova  = $noticia === null;
$acao   = $eNova ? site_url('admin/cms/noticias') : site_url('admin/cms/noticias/' . $noticia->id);
$erros  = session('erros');
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= $eNova ? 'Nova notícia' : 'Editar notícia' ?><?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <h1 class="h3 mb-0"><?= $eNova ? 'Nova notícia' : 'Editar notícia' ?></h1>
  <div class="d-flex align-items-center gap-2">
    <?php if (! $eNova): ?>
      <?= view('components/badge_estado', ['estado' => $noticia->status]) ?>
    <?php endif ?>
    <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/cms/noticias') ?>">Voltar</a>
  </div>
</div>

<form method="post" action="<?= esc($acao, 'attr') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">

    <!-- Coluna principal -->
    <div class="col-lg-8">
      <div class="cartao p-4">
        <?= view('components/campo', [
            'nome' => 'titulo', 'rotulo' => 'Título', 'obrigatorio' => true,
            'valor' => old('titulo', $noticia->titulo ?? ''), 'erros' => $erros,
        ]) ?>
        <?= view('components/campo', [
            'nome' => 'subtitulo', 'rotulo' => 'Subtítulo',
            'valor' => old('subtitulo', $noticia->subtitulo ?? ''), 'erros' => $erros,
        ]) ?>
        <?= view('components/campo', [
            'nome' => 'resumo', 'rotulo' => 'Resumo', 'tipo' => 'textarea', 'linhas' => 3,
            'ajuda' => 'Aparece nas listagens e no SEO se a meta-descrição estiver vazia.',
            'valor' => old('resumo', $noticia->resumo ?? ''), 'erros' => $erros,
        ]) ?>
        <?= view('components/campo', [
            'nome' => 'conteudo', 'rotulo' => 'Conteúdo', 'tipo' => 'textarea', 'linhas' => 16,
            'ajuda' => 'HTML permitido (sanitizado ao guardar).',
            'valor' => old('conteudo', $noticia->conteudo ?? ''), 'erros' => $erros,
        ]) ?>
      </div>

      <div class="cartao p-4 mt-3">
        <h2 class="h6 mb-3">SEO</h2>
        <?= view('components/campo', [
            'nome' => 'meta_titulo', 'rotulo' => 'Meta-título',
            'ajuda' => 'Se vazio, usa o título.',
            'valor' => old('meta_titulo', $noticia->meta_titulo ?? ''), 'erros' => $erros,
        ]) ?>
        <?= view('components/campo', [
            'nome' => 'meta_descricao', 'rotulo' => 'Meta-descrição', 'tipo' => 'textarea', 'linhas' => 2,
            'valor' => old('meta_descricao', $noticia->meta_descricao ?? ''), 'erros' => $erros,
        ]) ?>
      </div>
    </div>

    <!-- Coluna lateral: publicação e taxonomias -->
    <div class="col-lg-4">
      <div class="cartao p-4">
        <h2 class="h6 mb-3">Publicação</h2>

        <button class="btn btn-cns w-100 mb-2" type="submit">
          <i class="bi bi-save me-1"></i> <?= $eNova ? 'Criar rascunho' : 'Guardar alterações' ?>
        </button>

        <?php if (! $eNova && ! empty($transicoes)): ?>
          <hr>
          <p class="rotulo-secao mb-2">Ações</p>
          <?php
          $rotulos = [
              'submeter'  => ['Submeter para revisão', 'btn-cns-contorno'],
              'publicar'  => ['Publicar agora',        'btn-cns'],
              'agendar'   => ['Agendar publicação',    'btn-cns-contorno'],
              'devolver'  => ['Devolver a rascunho',   'btn-cns-contorno'],
              'arquivar'  => ['Arquivar',              'btn-cns-contorno'],
              'eliminar'  => ['Mover para a lixeira',  'btn-outline-danger'],
              'restaurar' => ['Restaurar',             'btn-cns-contorno'],
          ];
          ?>
          <?php foreach ($transicoes as $transicao => $destino): ?>
            <?php [$rotulo, $classe] = $rotulos[$transicao] ?? [ucfirst($transicao), 'btn-cns-contorno']; ?>

            <?php if ($transicao === 'agendar'): ?>
              <div class="mb-2">
                <label class="form-label small" for="data_agendada">Data e hora (Luanda)</label>
                <input class="form-control form-control-sm mb-2" type="datetime-local"
                       id="data_agendada" name="data_agendada"
                       form="form-<?= esc($transicao, 'attr') ?>">
              </div>
            <?php endif ?>

            <button class="btn <?= esc($classe, 'attr') ?> w-100 mb-2 btn-sm" type="submit"
                    form="form-<?= esc($transicao, 'attr') ?>">
              <?= esc($rotulo) ?>
            </button>
          <?php endforeach ?>
        <?php endif ?>
      </div>

      <div class="cartao p-4 mt-3">
        <h2 class="h6 mb-3">Organização</h2>

        <label class="form-label">Categorias</label>
        <div class="mb-3" style="max-height:180px;overflow-y:auto">
          <?php foreach ($categorias as $cat): ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="categorias[]"
                     value="<?= (int) $cat->id ?>" id="cat-<?= (int) $cat->id ?>"
                     <?= in_array($cat->id, $categoriasSelecionadas ?? [], true) ? 'checked' : '' ?>>
              <label class="form-check-label" for="cat-<?= (int) $cat->id ?>"><?= esc($cat->nome) ?></label>
            </div>
          <?php endforeach ?>
          <?php if (empty($categorias)): ?>
            <p class="text-muted small mb-0">Sem categorias criadas.</p>
          <?php endif ?>
        </div>

        <?= view('components/campo', [
            'nome' => 'tags', 'rotulo' => 'Etiquetas',
            'ajuda' => 'Separadas por vírgula. As novas são criadas automaticamente.',
            'valor' => old('tags', $tagsTexto ?? ''), 'erros' => $erros,
        ]) ?>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="destaque" value="1" id="destaque"
                 <?= ! empty($noticia->destaque) ? 'checked' : '' ?>>
          <label class="form-check-label" for="destaque">Destacar na página inicial</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="fixada" value="1" id="fixada"
                 <?= ! empty($noticia->fixada) ? 'checked' : '' ?>>
          <label class="form-check-label" for="fixada">Fixar no topo</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="permitir_comentarios" value="1"
                 id="permitir_comentarios" <?= ($noticia->permitir_comentarios ?? 1) ? 'checked' : '' ?>>
          <label class="form-check-label" for="permitir_comentarios">Permitir comentários</label>
        </div>
      </div>
    </div>
  </div>
</form>

<?php /* Formulários independentes para as transições de estado (fora do form principal) */ ?>
<?php if (! $eNova && ! empty($transicoes)): ?>
  <?php foreach ($transicoes as $transicao => $destino): ?>
    <form id="form-<?= esc($transicao, 'attr') ?>" method="post"
          action="<?= site_url('admin/cms/noticias/' . $noticia->id . '/transitar/' . $transicao) ?>"
          class="d-none">
      <?= csrf_field() ?>
    </form>
  <?php endforeach ?>
<?php endif ?>
<?= $this->endSection() ?>
