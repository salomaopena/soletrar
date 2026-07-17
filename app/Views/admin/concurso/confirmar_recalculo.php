<?php /** Confirmação de recálculo da classificação (GET → mostra; o botão faz o POST real). */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Recalcular classificação<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="cartao p-4" style="max-width:640px">
  <h1 class="h4 mb-3">Recalcular classificação</h1>
  <p><?= esc($evento->nome) ?></p>

  <p class="texto-suave">
    Isto substitui as posições finais atualmente gravadas por um novo cálculo,
    usando os critérios mais recentes (sobrevivência → pontos → tempo,
    com quem nunca chegou a soletrar sempre no fim da tabela).
  </p>

  <?php if ($jaHomologado): ?>
    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      Este evento <strong>já foi homologado</strong>. Recalcular corrige a tabela de
      participações, mas <strong>não desfaz</strong> sozinho progressões para a fase
      seguinte já criadas com os dados antigos. Depois de recalcular, reveja
      <a href="<?= site_url('admin/progressoes') ?>">Progressões</a> e corrija manualmente
      se for preciso.
    </div>
  <?php endif ?>

  <form method="post" action="<?= site_url('admin/eventos/' . $evento->id . '/recalcular') ?>">
    <?= csrf_field() ?>
    <div class="d-flex gap-2">
      <button class="btn btn-cns" type="submit">
        <i class="bi bi-arrow-clockwise me-1"></i> Confirmar recálculo
      </button>
      <a class="btn btn-cns-contorno" href="<?= site_url('admin/eventos/' . $evento->id) ?>">Cancelar</a>
    </div>
  </form>
</div>
<?= $this->endSection() ?>
