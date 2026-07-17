<?php
/**
 * Tela de login (Shield).
 *
 * NOTA DE COMPATIBILIDADE: escrita de forma defensiva porque este
 * ambiente não tinha o Shield instalado para confirmar ao pormenor os
 * nomes de variável da sua versão. Cobre as duas formas mais comuns de
 * o Shield expor erros de validação (sessão 'errors' em array, ou
 * $error singular) — se a sua versão usar outro nome, ajuste aqui.
 *
 * Campo de login: 'email' (convenção por omissão do Shield). Se o seu
 * Config\Auth::$validFields incluir também 'username', duplique o
 * bloco do campo abaixo com name="username".
 */
$erros = session()->getFlashdata('errors') ?? (isset($error) ? [$error] : []);
?>
<?= $this->extend('layouts/auth') ?>
<?= $this->section('titulo') ?>Entrar<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<h1 class="h5 text-center mb-1" style="color:var(--cns-marinho)">Área reservada</h1>
<p class="texto-suave text-center small mb-4">Acesso para coordenadores, júri e equipa editorial.</p>

<?php if (! empty($erros)): ?>
  <div class="alert alert-danger py-2 small">
    <?php foreach ((array) $erros as $e): ?>
      <div><?= esc(is_array($e) ? implode(' ', $e) : $e) ?></div>
    <?php endforeach ?>
  </div>
<?php endif ?>

<form method="post" action="<?= url_to('login') ?>">
  <?= csrf_field() ?>

  <div class="mb-3">
    <label class="form-label" for="email">E-mail</label>
    <input class="form-control" type="email" id="email" name="email"
           value="<?= old('email') ?>" required autofocus>
  </div>

  <div class="mb-3">
    <label class="form-label" for="password">Senha</label>
    <input class="form-control" type="password" id="password" name="password" required>
  </div>

  <div class="form-check mb-4">
    <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
    <label class="form-check-label small" for="remember">Manter sessão iniciada neste dispositivo</label>
  </div>

  <button class="btn btn-cns w-100" type="submit">
    <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
  </button>
</form>

<?php
// A rota do magic link chama-se 'magic-link' nesta versão do Shield.
// Envolvido em try/catch para NUNCA rebentar a tela de login inteira
// só porque um nome de rota não bateu certo.
try {
    $linkMagico = url_to('magic-link');
} catch (\Throwable) {
    $linkMagico = site_url('login/magic-link');
}
?>
<div class="rodape-auth">
  <a href="<?= esc($linkMagico, 'attr') ?>">Entrar sem senha (link por e-mail)</a>
  <span class="texto-suave mx-1">·</span>
  <a href="<?= site_url() ?>">Voltar ao portal</a>
</div>

<p class="text-center small texto-suave mt-3 mb-0">
  Não tem conta? As contas de coordenação são criadas pela administração —
  contacte a coordenação nacional.
</p>
<?= $this->endSection() ?>
