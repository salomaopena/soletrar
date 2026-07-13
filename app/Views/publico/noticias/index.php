<?= $this->extend('layouts/publico') ?>
<?= $this->section('titulo') ?>Notícias<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="container py-5">
  <h1 class="h3 mb-4">Notícias</h1>
  <div class="row g-4">
    <?php foreach ($noticias as $noticia): ?>
      <div class="col-md-4">
        <a class="cartao cartao--interativo p-3 h-100 d-block text-decoration-none text-reset"
           href="<?= esc($noticia->urlPublica(), 'attr') ?>">
          <h2 class="h6"><?= esc($noticia->titulo) ?></h2>
          <p class="texto-suave small mb-0"><?= esc(excerto($noticia->conteudo ?? '', 120)) ?></p>
        </a>
      </div>
    <?php endforeach ?>
  </div>
  <div class="mt-4"><?= $pager->links() ?? '' ?></div>
</div>
<?= $this->endSection() ?>
