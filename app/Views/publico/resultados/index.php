<?php /** Lista pública de EDIÇÕES com resultados homologados. */ ?>
<?= $this->extend('layouts/publico') ?>
<?= $this->section('titulo') ?>Resultados<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="container py-5">
  <h1 class="h3 mb-1">Resultados</h1>
  <p class="texto-suave mb-4">Resultados oficiais, homologados pela coordenação.</p>

  <div class="row g-3">
    <?php foreach ($edicoes as $ed): ?>
      <div class="col-md-6 col-lg-4">
        <a class="cartao cartao--interativo p-4 d-block text-decoration-none text-reset h-100"
           href="<?= site_url('resultados/edicao/' . $ed->id) ?>">
          <p class="rotulo-secao mb-1"><?= (int) $ed->ano ?></p>
          <div class="fw-semibold mb-2"><?= esc($ed->nome) ?></div>
          <span class="texto-suave small">
            <?= (int) $ed->eventos_homologados ?> evento(s) com resultados publicados
          </span>
        </a>
      </div>
    <?php endforeach ?>

    <?php if (empty($edicoes)): ?>
      <div class="col-12">
        <?= view('components/estado_vazio', [
            'palavra'  => 'breve',
            'mensagem' => 'Ainda não há resultados homologados para publicar.',
        ]) ?>
      </div>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
