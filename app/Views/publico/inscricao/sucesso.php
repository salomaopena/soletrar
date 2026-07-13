<?php /* Comprovativo de inscrição. Dados: $inscricao, $token. */ ?>
<?= $this->extend('layouts/publico') ?>
<?= $this->section('titulo') ?>Inscrição submetida<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="container py-5 text-center" style="max-width:620px">
  <span class="fichas justify-content-center mb-4" aria-hidden="true">
    <?php foreach (mb_str_split('feito') as $l): ?><span class="ficha-letra"><?= $l ?></span><?php endforeach ?>
  </span>

  <h1 class="mb-2">Inscrição submetida com sucesso</h1>
  <p class="texto-suave mb-4">
    Guarde o número de inscrição. Enviámos um e-mail ao encarregado com o
    link de acompanhamento. A inscrição aguarda validação pela coordenação.
  </p>

  <div class="cartao p-4 mb-4 text-start">
    <div class="row g-3">
      <div class="col-6"><div class="rotulo-secao">Número</div><div class="fw-bold fs-5"><?= esc($inscricao->numero_inscricao) ?></div></div>
      <div class="col-6"><div class="rotulo-secao">Estado</div><div><?= view('components/badge_estado', ['estado' => $inscricao->status]) ?></div></div>
      <div class="col-6"><div class="rotulo-secao">Candidato</div><div class="fw-semibold"><?= esc($inscricao->nome_completo) ?></div></div>
      <div class="col-6"><div class="rotulo-secao">Província</div><div><?= esc($inscricao->provincia) ?></div></div>
    </div>
  </div>

  <a class="btn btn-cns" href="<?= site_url('inscricao/estado/' . $token) ?>">Acompanhar estado</a>
  <a class="btn btn-cns-contorno ms-2" href="<?= site_url() ?>">Voltar ao início</a>
</div>
<?= $this->endSection() ?>
