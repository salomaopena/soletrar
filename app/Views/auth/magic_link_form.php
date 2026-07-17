<?php
/**
 * Pedido de link de acesso ("magic link") — Shield.
 *
 * Corresponde à chave 'magic-link-login' de Config\Auth::$views.
 * NÃO é um "recuperar senha" tradicional: o link recebido por e-mail
 * faz login diretamente, sem definir nova senha.
 */
$erros = session()->getFlashdata('errors') ?? (isset($error) ? [$error] : []);
?>
<?= $this->extend('layouts/auth') ?>
<?= $this->section('titulo') ?>Entrar sem senha<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<h1 class="h5 text-center mb-1" style="color:var(--cns-marinho)">Entrar sem senha</h1>
<p class="texto-suave text-center small mb-4">
  Indique o e-mail da sua conta — enviamos um link que faz login
  diretamente, sem precisar de senha.
</p>

<?php if (! empty($erros)): ?>
  <div class="alert alert-danger py-2 small">
    <?php foreach ((array) $erros as $e): ?>
      <div><?= esc(is_array($e) ? implode(' ', $e) : $e) ?></div>
    <?php endforeach ?>
  </div>
<?php endif ?>

<?php
try { $acao = url_to('magic-link'); } catch (\Throwable) { $acao = site_url('login/magic-link'); }
?>
<form method="post" action="<?= esc($acao, 'attr') ?>">
  <?= csrf_field() ?>

  <div class="mb-4">
    <label class="form-label" for="email">E-mail</label>
    <input class="form-control" type="email" id="email" name="email"
           value="<?= old('email') ?>" required autofocus>
  </div>

  <button class="btn btn-cns w-100" type="submit">
    <i class="bi bi-envelope-paper me-1"></i> Enviar link de acesso
  </button>
</form>

<div class="rodape-auth">
  <a href="<?= url_to('login') ?>"><i class="bi bi-arrow-left me-1"></i> Voltar ao login com senha</a>
</div>
<?= $this->endSection() ?>
