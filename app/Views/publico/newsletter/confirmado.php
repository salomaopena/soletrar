<?= $this->extend('layouts/publico') ?>
<?= $this->section('titulo') ?>Subscrição confirmada<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="container py-5 text-center" style="max-width:520px">
  <h1 class="mb-3">Subscrição confirmada</h1>
  <p class="texto-suave">Obrigado. Passará a receber as novidades do concurso.</p>
  <a class="btn btn-cns mt-2" href="<?= site_url() ?>">Voltar ao início</a>
</div>
<?= $this->endSection() ?>
