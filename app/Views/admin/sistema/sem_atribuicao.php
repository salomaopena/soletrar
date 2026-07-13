<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Sem atribuição<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="cartao p-5 text-center">
  <h1 class="h4 mb-3">Ainda não tem território atribuído</h1>
  <p class="texto-suave">A sua conta de coordenação existe, mas ainda não tem uma província,
     município ou escola associada. Contacte a coordenação nacional para ativar o seu acesso.</p>
</div>
<?= $this->endSection() ?>
