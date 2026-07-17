<?php /** Confirmação após pedir o link de acesso. Chave 'magic-link-message'. */ ?>
<?= $this->extend('layouts/auth') ?>
<?= $this->section('titulo') ?>Verifique o seu e-mail<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="text-center">
  <i class="bi bi-envelope-check" style="font-size:2.6rem;color:var(--cns-verde-agua)"></i>
  <h1 class="h5 mt-3 mb-2" style="color:var(--cns-marinho)">Verifique o seu e-mail</h1>
  <p class="texto-suave small mb-4">
    Se o e-mail indicado corresponder a uma conta, enviámos um link de
    acesso. Abra-o no mesmo dispositivo onde quer entrar — ele expira
    ao fim de algum tempo, por segurança.
  </p>
</div>

<div class="rodape-auth">
  <a href="<?= url_to('login') ?>"><i class="bi bi-arrow-left me-1"></i> Voltar ao login</a>
</div>
<?= $this->endSection() ?>
