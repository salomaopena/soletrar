<?php /** Configurações: acessos ocasionais, agrupados em cartões. */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Configurações<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<h1 class="h3 mb-1">Configurações</h1>
<p class="texto-suave mb-4">
  Tudo o que se configura de vez em quando. O menu lateral fica reservado ao trabalho do dia-a-dia.
</p>

<?php foreach ($secoes as $grupo => $cartoes): ?>
  <p class="rotulo-secao mt-4 mb-2"><?= esc($grupo) ?></p>
  <div class="row g-3">
    <?php foreach ($cartoes as [$rota, $icone, $titulo, $descricao, $perm]): ?>
      <div class="col-sm-6 col-lg-4 col-xl-3">
        <a class="cartao cartao--interativo p-3 h-100 d-block text-decoration-none text-reset"
           href="<?= site_url($rota) ?>">
          <div class="d-flex align-items-start gap-3">
            <span class="icone-config"><i class="bi <?= esc($icone, 'attr') ?>"></i></span>
            <div>
              <div class="fw-semibold" style="color:var(--cns-marinho)"><?= esc($titulo) ?></div>
              <div class="small texto-suave"><?= esc($descricao) ?></div>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach ?>
  </div>
<?php endforeach ?>

<?php if (empty($secoes)): ?>
  <?= view('components/estado_vazio', [
      'palavra'  => 'zero',
      'mensagem' => 'Não tem permissões para gerir configurações.',
  ]) ?>
<?php endif ?>
<?= $this->endSection() ?>
