<?= $this->extend('layouts/publico') ?>
<?= $this->section('titulo') ?><?= esc($pagina->meta_titulo ?: $pagina->titulo) ?><?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<article class="container py-5 artigo">
  <h1><?= esc($pagina->titulo) ?></h1>
  <div class="conteudo"><?= $pagina->conteudo /* sanitizado na entrada */ ?></div>
</article>
<?= $this->endSection() ?>
