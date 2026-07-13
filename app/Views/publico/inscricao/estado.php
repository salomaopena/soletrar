<?= $this->extend('layouts/publico') ?>
<?= $this->section('titulo') ?>Estado da inscrição<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="container py-5" style="max-width:620px">
  <h1 class="h3 mb-4">Estado da inscrição</h1>
  <div class="cartao p-4">
    <div class="row g-3">
      <div class="col-6"><div class="rotulo-secao">Número</div><div class="fw-bold"><?= esc($inscricao->numero_inscricao) ?></div></div>
      <div class="col-6"><div class="rotulo-secao">Estado</div><div><?= view('components/badge_estado', ['estado' => $inscricao->status]) ?></div></div>
      <div class="col-6"><div class="rotulo-secao">Candidato</div><div class="fw-semibold"><?= esc($inscricao->nome_completo) ?></div></div>
      <div class="col-6"><div class="rotulo-secao">Categoria</div><div><?= esc($inscricao->categoria ?? '') ?></div></div>
    </div>
    <?php if ($inscricao->status === 'rejeitada' && ! empty($inscricao->motivo_rejeicao)): ?>
      <div class="alert alert-danger mt-3 mb-0"><strong>Motivo:</strong> <?= esc($inscricao->motivo_rejeicao) ?></div>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
