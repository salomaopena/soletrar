<?php
/**
 * Formulário CRUD genérico (criar/editar).
 *
 * Parâmetros:
 *   $titulo   string
 *   $rotaBase string
 *   $registo  object|null   (null = criação)
 *   $campos   array de definições:
 *      ['nome' => 'nome', 'rotulo' => 'Nome', 'tipo' => 'text|email|date|datetime-local|number|select|textarea|checkbox',
 *       'obrigatorio' => bool, 'opcoes' => [chave=>texto], 'ajuda' => '...', 'largura' => 6]
 */
$eNovo = $registo === null;
$acao  = $eNovo ? site_url($rotaBase) : site_url($rotaBase . '/' . $registo->id);
$erros = session('erros');
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= esc($titulo) ?><?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <h1 class="h3 mb-0"><?= esc($titulo) ?></h1>
  <a class="btn btn-cns-contorno btn-sm" href="<?= site_url($rotaBase) ?>">Voltar à lista</a>
</div>

<form method="post" action="<?= esc($acao, 'attr') ?>" class="cartao p-4" style="max-width:900px">
  <?= csrf_field() ?>
  <div class="row">
    <?php foreach ($campos as $c): ?>
      <?php $valorAtual = old($c['nome'], $registo->{$c['nome']} ?? ($c['valor'] ?? '')); ?>

      <?php if (($c['tipo'] ?? 'text') === 'checkbox'): ?>
        <div class="col-12 mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1"
                   id="campo-<?= esc($c['nome'], 'attr') ?>" name="<?= esc($c['nome'], 'attr') ?>"
                   <?= $valorAtual ? 'checked' : '' ?>>
            <label class="form-check-label" for="campo-<?= esc($c['nome'], 'attr') ?>">
              <?= esc($c['rotulo']) ?>
            </label>
          </div>
        </div>
      <?php else: ?>
        <div class="col-md-<?= (int) ($c['largura'] ?? 12) ?>">
          <?= view('components/campo', [
              'nome'        => $c['nome'],
              'rotulo'      => $c['rotulo'],
              'tipo'        => $c['tipo'] ?? 'text',
              'valor'       => $valorAtual,
              'obrigatorio' => $c['obrigatorio'] ?? false,
              'opcoes'      => $c['opcoes'] ?? [],
              'ajuda'       => $c['ajuda'] ?? null,
              'linhas'      => $c['linhas'] ?? 4,
              'erros'       => $erros,
          ]) ?>
        </div>
      <?php endif ?>
    <?php endforeach ?>
  </div>

  <div class="d-flex gap-2 mt-2">
    <button class="btn btn-cns" type="submit">
      <i class="bi bi-save me-1"></i> <?= $eNovo ? 'Adicionar' : 'Guardar alterações' ?>
    </button>
    <a class="btn btn-cns-contorno" href="<?= site_url($rotaBase) ?>">Cancelar</a>
  </div>
</form>
<?= $this->endSection() ?>
