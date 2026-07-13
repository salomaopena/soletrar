<?= $this->extend('layouts/publico') ?>
<?= $this->section('titulo') ?>Resultados<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="container py-5">
  <h1 class="h3 mb-4">Resultados</h1>
  <div class="row g-3">
    <?php foreach ($eventos as $ev): ?>
      <div class="col-md-6">
        <a class="cartao cartao--interativo p-3 d-block text-decoration-none text-reset"
           href="<?= site_url('resultados/evento/' . $ev->id) ?>">
          <div class="fw-semibold"><?= esc($ev->nome) ?></div>
          <div class="texto-suave small"><?= esc(data_exibir($ev->data_evento, 'longa')) ?></div>
        </a>
      </div>
    <?php endforeach ?>
    <?php if (empty($eventos)): ?>
      <div class="col-12"><?= view('components/estado_vazio', ['palavra' => 'breve', 'mensagem' => 'Ainda não há resultados publicados.']) ?></div>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
