<?php
/**
 * Criação de utilizador: conta (Shield) + perfil + âmbito territorial.
 * Os campos de território só aparecem para grupos que os exigem.
 */
$erros = session('erros');
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Adicionar utilizador<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">Adicionar utilizador</h1>
  <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/sistema/utilizadores') ?>">Voltar</a>
</div>

<form method="post" action="<?= site_url('admin/sistema/utilizadores') ?>"
      class="cartao p-4" style="max-width:900px">
  <?= csrf_field() ?>

  <h2 class="h6 mb-3">Conta de acesso</h2>
  <div class="row">
    <div class="col-md-4">
      <?= view('components/campo', ['nome' => 'username', 'rotulo' => 'Nome de utilizador',
          'tipo' => 'text', 'obrigatorio' => true, 'valor' => old('username'), 'erros' => $erros]) ?>
    </div>
    <div class="col-md-4">
      <?= view('components/campo', ['nome' => 'email', 'rotulo' => 'E-mail',
          'tipo' => 'email', 'obrigatorio' => true, 'valor' => old('email'), 'erros' => $erros]) ?>
    </div>
    <div class="col-md-4">
      <?= view('components/campo', ['nome' => 'password', 'rotulo' => 'Senha inicial',
          'tipo' => 'password', 'obrigatorio' => true, 'valor' => '',
          'ajuda' => 'Mínimo 8 caracteres.', 'erros' => $erros]) ?>
    </div>
  </div>

  <h2 class="h6 mb-3 mt-2">Dados pessoais</h2>
  <div class="row">
    <div class="col-md-6">
      <?= view('components/campo', ['nome' => 'nome_completo', 'rotulo' => 'Nome completo',
          'tipo' => 'text', 'obrigatorio' => true, 'valor' => old('nome_completo'), 'erros' => $erros]) ?>
    </div>
    <div class="col-md-3">
      <?= view('components/campo', ['nome' => 'telefone', 'rotulo' => 'Telefone',
          'tipo' => 'tel', 'valor' => old('telefone'), 'ajuda' => '9XXXXXXXX', 'erros' => $erros]) ?>
    </div>
    <div class="col-md-3">
      <?= view('components/campo', ['nome' => 'genero', 'rotulo' => 'Género', 'tipo' => 'select',
          'opcoes' => ['feminino' => 'Feminino', 'masculino' => 'Masculino'],
          'valor' => old('genero'), 'erros' => $erros]) ?>
    </div>
  </div>

  <h2 class="h6 mb-3 mt-2">Perfil e âmbito</h2>
  <div class="row">
    <div class="col-md-4">
      <?= view('components/campo', ['nome' => 'grupo', 'rotulo' => 'Grupo (perfil)', 'tipo' => 'select',
          'obrigatorio' => true, 'opcoes' => $grupos, 'valor' => old('grupo'), 'erros' => $erros]) ?>
    </div>
    <div class="col-md-8">
      <div class="alert alert-info small mb-3" id="avisoAmbito">
        Coordenadores <strong>precisam</strong> de território atribuído — sem ele
        não veem quaisquer dados (por segurança).
      </div>
    </div>
  </div>

  <div class="row" id="camposTerritorio">
    <div class="col-md-4">
      <?= view('components/campo', ['nome' => 'provincia_id', 'rotulo' => 'Província', 'tipo' => 'select',
          'opcoes' => $provincias, 'valor' => old('provincia_id'), 'erros' => $erros]) ?>
    </div>
    <div class="col-md-4">
      <?= view('components/campo', ['nome' => 'municipio_id', 'rotulo' => 'Município', 'tipo' => 'select',
          'opcoes' => $municipios, 'valor' => old('municipio_id'), 'erros' => $erros]) ?>
    </div>
    <div class="col-md-4">
      <?= view('components/campo', ['nome' => 'escola_id', 'rotulo' => 'Escola', 'tipo' => 'select',
          'opcoes' => $escolas, 'valor' => old('escola_id'), 'erros' => $erros]) ?>
    </div>
  </div>

  <button class="btn btn-cns" type="submit">
    <i class="bi bi-person-plus me-1"></i> Criar utilizador
  </button>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Mostra os campos de território apenas para grupos que o exigem.
const territoriais = ['coord_provincial', 'coord_municipal', 'coord_escolar', 'professor'];
const grupo   = document.getElementById('campo-grupo');
const bloco   = document.getElementById('camposTerritorio');
const aviso   = document.getElementById('avisoAmbito');

function alternar() {
  const precisa = territoriais.includes(grupo.value);
  bloco.style.display = precisa ? '' : 'none';
  aviso.style.display = precisa ? '' : 'none';
}
grupo.addEventListener('change', alternar);
alternar();
</script>
<?= $this->endSection() ?>
