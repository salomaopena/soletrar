<?= $this->extend('layouts/publico') ?>
<?= $this->section('titulo') ?>Inscrições encerradas<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="container py-5 text-center" style="max-width:560px">
  <h1 class="mb-3">Inscrições encerradas</h1>
  <p class="texto-suave">De momento não há nenhuma edição com inscrições abertas.
     Volte mais tarde ou acompanhe as nossas notícias.</p>
  <a class="btn btn-cns mt-2" href="<?= site_url() ?>">Voltar ao início</a>
</div>
<?= $this->endSection() ?>
