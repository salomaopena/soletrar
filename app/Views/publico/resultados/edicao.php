<?php /** Agregado de resultados de uma edição, agrupado por fase. */ ?>
<?= $this->extend('layouts/publico') ?>
<?= $this->section('titulo') ?>Resultados · <?= esc($edicao->nome) ?><?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="container py-5">
  <p class="rotulo-secao mb-1"><?= (int) $edicao->ano ?></p>
  <h1 class="h3 mb-4"><?= esc($edicao->nome) ?></h1>

  <?php if (empty($porFase)): ?>
    <?= view('components/estado_vazio', [
        'palavra'  => 'breve',
        'mensagem' => 'Ainda não há resultados homologados nesta edição.',
    ]) ?>
  <?php else: ?>
    <?php foreach ($porFase as $faseNome => $eventos): ?>
      <h2 class="h5 mb-3 mt-4"><?= esc($faseNome) ?></h2>
      <div class="row g-3 mb-2">
        <?php foreach ($eventos as $ev): ?>
          <div class="col-md-6">
            <a class="cartao cartao--interativo p-3 d-block text-decoration-none text-reset"
               href="<?= site_url('resultados/evento/' . $ev->id) ?>">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold"><?= esc($ev->nome) ?></div>
                  <div class="texto-suave small">
                    <?= esc($ev->categoria_nome ?? '') ?> ·
                    <?= esc(data_exibir($ev->data_evento, 'longa')) ?>
                  </div>
                </div>
              </div>

              <?php if (! empty($podios[$ev->id])): ?>
                <ol class="ps-3 mt-2 mb-0 small">
                  <?php foreach ($podios[$ev->id] as $p): ?>
                    <li><?= esc($p['nome_completo']) ?></li>
                  <?php endforeach ?>
                </ol>
              <?php endif ?>
            </a>
          </div>
        <?php endforeach ?>
      </div>
    <?php endforeach ?>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
