<?php
/** Perfil do utilizador (perfis_utilizador) + âmbito territorial. */
$erros = session('erros');
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>O meu perfil<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<h1 class="h3 mb-4">O meu perfil</h1>

<div class="row g-3">
  <div class="col-lg-7">
    <form method="post" action="<?= site_url('admin/perfil') ?>" class="cartao p-4">
      <?= csrf_field() ?>
      <h2 class="h6 mb-3">Dados pessoais</h2>

      <div class="row">
        <div class="col-md-8">
          <?= view('components/campo', [
              'nome' => 'nome_completo', 'rotulo' => 'Nome completo', 'obrigatorio' => true,
              'valor' => old('nome_completo', $perfil->nome_completo ?? ''), 'erros' => $erros,
          ]) ?>
        </div>
        <div class="col-md-4">
          <?= view('components/campo', [
              'nome' => 'genero', 'rotulo' => 'Género', 'tipo' => 'select',
              'opcoes' => ['feminino' => 'Feminino', 'masculino' => 'Masculino'],
              'valor' => old('genero', $perfil->genero ?? ''), 'erros' => $erros,
          ]) ?>
        </div>
        <div class="col-md-6">
          <?= view('components/campo', [
              'nome' => 'telefone', 'rotulo' => 'Telefone', 'ajuda' => '9XXXXXXXX',
              'valor' => old('telefone', $perfil->telefone ?? ''), 'erros' => $erros,
          ]) ?>
        </div>
        <div class="col-md-6">
          <?= view('components/campo', [
              'nome' => 'bi_numero', 'rotulo' => 'Bilhete de identidade',
              'ajuda' => 'Ex.: 001234567LA041',
              'valor' => old('bi_numero', $perfil->bi_numero ?? ''), 'erros' => $erros,
          ]) ?>
        </div>
      </div>

      <button class="btn btn-cns" type="submit"><i class="bi bi-save me-1"></i> Guardar</button>
    </form>
  </div>

  <div class="col-lg-5">
    <div class="cartao p-4 mb-3">
      <h2 class="h6 mb-3">Conta</h2>
      <div class="row g-3">
        <div class="col-6"><div class="rotulo-secao">Utilizador</div>
          <div class="fw-semibold"><?= esc($utilizador->username ?? '') ?></div></div>
        <div class="col-6"><div class="rotulo-secao">E-mail</div>
          <div class="small"><?= esc($utilizador->email ?? '—') ?></div></div>
        <div class="col-12"><div class="rotulo-secao">Grupos</div>
          <div>
            <?php foreach ($utilizador->getGroups() as $g): ?>
              <span class="badge text-bg-light"><?= esc($g) ?></span>
            <?php endforeach ?>
          </div>
        </div>
      </div>
    </div>

    <div class="cartao p-4">
      <h2 class="h6 mb-3">Âmbito territorial</h2>
      <p class="mb-2"><span class="badge-estado badge-estado--validada"><?= esc($escopo->nivel) ?></span></p>

      <?php if (empty($atribuicoes)): ?>
        <p class="texto-suave small mb-0">Sem atribuições específicas registadas.</p>
      <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($atribuicoes as $a): ?>
            <li class="list-group-item px-0 small d-flex justify-content-between">
              <span><?= esc($a->escola ?? $a->municipio ?? $a->provincia ?? 'Nacional') ?></span>
              <span class="badge text-bg-light"><?= esc($a->nivel) ?></span>
            </li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
