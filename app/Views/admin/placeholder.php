<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= esc($titulo ?? 'Em construção') ?><?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<h1 class="h3 mb-4"><?= esc($titulo ?? 'Módulo') ?></h1>
<div class="cartao p-5">
  <?= view('components/estado_vazio', [
      'palavra'  => 'breve',
      'mensagem' => 'Este módulo será implementado seguindo os padrões já definidos (controller fino → service → model com escopo → componentes da Fase 8).',
  ]) ?>
</div>
<?= $this->endSection() ?>
