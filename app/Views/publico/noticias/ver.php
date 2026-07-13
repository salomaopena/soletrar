<?php /* Página de notícia. Dados: $noticia (entity), $comentarios, $relacionadas. */ ?>
<?= $this->extend('layouts/publico') ?>
<?= $this->section('titulo') ?><?= esc($noticia->metaTitulo()) ?><?= $this->endSection() ?>

<?= $this->section('meta') ?>
  <meta name="description" content="<?= esc($noticia->metaDescricao(), 'attr') ?>">
  <link rel="canonical" href="<?= esc($noticia->urlPublica(), 'attr') ?>">
  <meta property="og:title" content="<?= esc($noticia->metaTitulo(), 'attr') ?>">
  <meta property="og:description" content="<?= esc($noticia->metaDescricao(), 'attr') ?>">
  <meta property="og:image" content="<?= esc($noticia->ogImagem(), 'attr') ?>">
  <meta property="og:type" content="article">
<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<article class="container py-5 artigo">
  <header class="mb-4">
    <?php if (! empty($noticia->categoria_principal)): ?>
      <a class="etiqueta-categoria" href="<?= site_url('noticias/categoria/' . esc($noticia->categoria_slug, 'url')) ?>">
        <?= esc($noticia->categoria_principal) ?>
      </a>
    <?php endif ?>
    <h1 class="mt-2 mb-2"><?= esc($noticia->titulo) ?></h1>
    <?php if ($noticia->subtitulo): ?>
      <p class="lead texto-suave"><?= esc($noticia->subtitulo) ?></p>
    <?php endif ?>
    <p class="meta mb-0">
      <?= esc(data_exibir($noticia->data_publicacao, 'longa')) ?>
      · <?= (int) $noticia->tempoLeituraMin() ?> min de leitura
    </p>
  </header>

  <?php if (! empty($noticia->imagem_destacada_url)): ?>
    <figure class="mb-4">
      <img class="imagem-destacada" src="<?= esc(base_url($noticia->imagem_destacada_url), 'attr') ?>"
           alt="<?= esc($noticia->imagem_alt ?? $noticia->titulo, 'attr') ?>">
    </figure>
  <?php endif ?>

  <div class="conteudo">
    <?= $noticia->conteudo /* sanitizado na ENTRADA pelo SanitizadorHtml (Fase 5) */ ?>
  </div>

  <?php if ($noticia->permitir_comentarios): ?>
    <hr class="my-5">
    <section aria-labelledby="titulo-comentarios">
      <h2 id="titulo-comentarios" class="h4 mb-4">Comentários</h2>

      <?php foreach ($comentarios as $comentario): ?>
        <div class="cartao p-3 mb-3">
          <p class="mb-1 fw-semibold"><?= esc($comentario['nome_autor'] ?? 'Utilizador') ?>
            <span class="texto-suave fw-normal small ms-1"><?= esc(data_exibir($comentario['created_at'], 'curta')) ?></span>
          </p>
          <p class="mb-0"><?= esc($comentario['conteudo']) ?></p>
        </div>
      <?php endforeach ?>

      <form class="cartao p-4 mt-4" method="post" action="<?= site_url('noticias/comentar') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="noticia_id" value="<?= (int) $noticia->id ?>">
        <?php /* Honeypot anti-spam (Fase 5): invisível para pessoas */ ?>
        <div class="visually-hidden" aria-hidden="true">
          <label>Não preencher<input type="text" name="website_confirmar" tabindex="-1" autocomplete="off"></label>
        </div>
        <?= view('components/campo', ['nome' => 'nome_autor', 'rotulo' => 'Nome', 'obrigatorio' => true, 'valor' => old('nome_autor'), 'erros' => session('erros')]) ?>
        <?= view('components/campo', ['nome' => 'conteudo', 'rotulo' => 'Comentário', 'tipo' => 'textarea', 'obrigatorio' => true, 'valor' => old('conteudo'), 'erros' => session('erros')]) ?>
        <button class="btn btn-cns" type="submit">Publicar comentário</button>
        <p class="form-text mt-2">Os comentários passam por moderação antes de ficarem visíveis.</p>
      </form>
    </section>
  <?php endif ?>
</article>
<?= $this->endSection() ?>
