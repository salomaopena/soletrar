<?= $this->extend('layouts/publico') ?>
<?= $this->section('titulo') ?>Concurso Nacional de Soletração — Angola<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>

<header class="hero-portal">
  <div class="container">
    <p class="rotulo-secao mb-2">Concurso Nacional de Soletração</p>
    <h1>A língua portuguesa em palco, letra a letra.</h1>
    <p class="lead texto-suave mt-3" style="max-width:52ch">
      Alunos até à 8.ª classe de todas as províncias de Angola competem
      da escola à final nacional.
    </p>
    <div class="d-flex gap-2 mt-4 flex-wrap">
      <a class="btn btn-cns" href="<?= site_url('inscricao') ?>">Inscrever candidato</a>
      <a class="btn btn-cns-contorno" href="<?= site_url('resultados') ?>">Ver resultados</a>
    </div>
  </div>
</header>

<div class="container pb-5">
  <h2 class="h4 mb-4">Últimas notícias</h2>
  <div class="row g-4">
    <?php foreach ($destaques as $noticia): ?>
      <div class="col-md-4">
        <a class="cartao cartao--interativo p-3 h-100 d-block text-decoration-none text-reset"
          href="<?= esc($noticia->urlPublica(), 'attr') ?>">
          <h3 class="h6"><?= esc($noticia->titulo) ?></h3>
          <p class="texto-suave small mb-0"><?= esc(excerto($noticia->conteudo ?? '', 120)) ?></p>
        </a>
      </div>
    <?php endforeach ?>
    <?php if ($destaques === []): ?>
      <div class="col-12">
        <?= view('components/estado_vazio', ['palavra' => 'breve', 'mensagem' => 'Ainda não há notícias publicadas.']) ?>
      </div>
    <?php endif ?>
  </div>
</div>

<?php if (!empty($patrocinadores)): ?>
  <section class="container pb-5">
    <p class="rotulo-secao mb-2 text-center">Com o apoio de</p>
    <h2 class="h4 mb-4 text-center">Parceiros e patrocinadores</h2>

    <?php
    // Bootstrap carousel, 4 logótipos por slide (ajusta em telemóvel via CSS).
    $grupos = array_chunk($patrocinadores, 4);
    ?>
    <div id="carrosselPatrocinadores" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
      <div class="carousel-inner">
        <?php foreach ($grupos as $indice => $grupo): ?>
          <div class="carousel-item <?= $indice === 0 ? 'active' : '' ?>">
            <div class="row justify-content-center align-items-center g-4">
              <?php foreach ($grupo as $p): ?>
                <div class="col-6 col-md-3 text-center">
                  <?php if ($p->website): ?><a href="<?= esc($p->website, 'attr') ?>" target="_blank"
                      rel="noopener sponsored"><?php endif ?>
                    <?php if ($p->logo_url): ?>
                      <img src="<?= esc($p->logo_url, 'attr') ?>" alt="<?= esc($p->nome, 'attr') ?>" class="img-fluid"
                        style="max-height:64px;filter:grayscale(40%);opacity:.85">
                    <?php else: ?>
                      <img src="<?= base_url('public/assets/img/logo_wine.png') ?>" alt="Logo" class="img-fluid"
                        style="max-height:64px;filter:grayscale(40%);opacity:.85">
                    <?php endif ?>
                    <?php if ($p->website): ?></a><?php endif ?>
                </div>
              <?php endforeach ?>
            </div>
          </div>
        <?php endforeach ?>
      </div>

      <?php if (count($grupos) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#carrosselPatrocinadores" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" style="filter:invert(1) grayscale(1)" aria-hidden="true"></span>
          <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carrosselPatrocinadores" data-bs-slide="next">
          <span class="carousel-control-next-icon" style="filter:invert(1) grayscale(1)" aria-hidden="true"></span>
          <span class="visually-hidden">Seguinte</span>
        </button>
      <?php endif ?>
    </div>
  </section>
<?php endif ?>

<?= $this->endSection() ?>