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
          <?= view('components/campo', ['nome' => 'nome_completo', 'rotulo' => 'Nome completo',
              'tipo' => 'text', 'obrigatorio' => true,
              'valor' => old('nome_completo', $perfil->nome_completo ?? ''), 'erros' => $erros]) ?>
        </div>
        <div class="col-md-4">
          <?= view('components/campo', ['nome' => 'genero', 'rotulo' => 'Género', 'tipo' => 'select',
              'opcoes' => ['F' => 'Feminino', 'M' => 'Masculino'],
              'valor' => old('genero', $perfil->genero ?? ''), 'erros' => $erros]) ?>
        </div>

        <div class="col-md-4">
          <?= view('components/campo', ['nome' => 'data_nascimento', 'rotulo' => 'Data de nascimento',
              'tipo' => 'date',
              'valor' => old('data_nascimento', $perfil->data_nascimento ?? ''), 'erros' => $erros]) ?>
        </div>
        <div class="col-md-4">
          <?= view('components/campo', ['nome' => 'bi_numero', 'rotulo' => 'Bilhete de identidade',
              'tipo' => 'text', 'ajuda' => 'Ex.: 001234567LA041',
              'valor' => old('bi_numero', $perfil->bi_numero ?? ''), 'erros' => $erros]) ?>
        </div>
        <div class="col-md-4">
          <?= view('components/campo', ['nome' => 'idiomas', 'rotulo' => 'Idiomas',
              'tipo' => 'text', 'ajuda' => 'Separados por vírgula.',
              'valor' => old('idiomas', $perfil->idiomas ?? ''), 'erros' => $erros]) ?>
        </div>
      </div>

      <h2 class="h6 mb-3 mt-2">Contactos e morada</h2>
      <div class="row">
        <div class="col-md-4">
          <?= view('components/campo', ['nome' => 'telefone', 'rotulo' => 'Telefone',
              'tipo' => 'tel', 'ajuda' => '9XXXXXXXX',
              'valor' => old('telefone', $perfil->telefone ?? ''), 'erros' => $erros]) ?>
        </div>
        <div class="col-md-4">
          <?= view('components/campo', ['nome' => 'telefone_alt', 'rotulo' => 'Telefone alternativo',
              'tipo' => 'tel',
              'valor' => old('telefone_alt', $perfil->telefone_alt ?? ''), 'erros' => $erros]) ?>
        </div>
        <div class="col-md-4">
          <?= view('components/campo', ['nome' => 'foto', 'rotulo' => 'Foto (URL)', 'tipo' => 'text',
              'valor' => old('foto', $perfil->foto ?? ''), 'erros' => $erros]) ?>
        </div>

        <div class="col-md-4">
          <?= view('components/campo', ['nome' => 'provincia_id', 'rotulo' => 'Província',
              'tipo' => 'select', 'opcoes' => $provincias,
              'valor' => old('provincia_id', $perfil->provincia_id ?? ''), 'erros' => $erros]) ?>
        </div>
        <div class="col-md-4">
          <?= view('components/campo', ['nome' => 'municipio_id', 'rotulo' => 'Município',
              'tipo' => 'select', 'opcoes' => $municipios,
              'valor' => old('municipio_id', $perfil->municipio_id ?? ''), 'erros' => $erros]) ?>
        </div>
        <div class="col-md-4">
          <?= view('components/campo', ['nome' => 'endereco', 'rotulo' => 'Endereço', 'tipo' => 'text',
              'valor' => old('endereco', $perfil->endereco ?? ''), 'erros' => $erros]) ?>
        </div>

        <div class="col-12">
          <?= view('components/campo', ['nome' => 'bio', 'rotulo' => 'Biografia',
              'tipo' => 'textarea', 'linhas' => 3,
              'valor' => old('bio', $perfil->bio ?? ''), 'erros' => $erros]) ?>
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
