<?php /* Detalhe de inscrição com ações de validação (Fase 4/6).
   O ID vem cifrado da URL; os formulários reenviam o mesmo token. */
$token = id_cifrar($inscricao->id, 'inscricao');
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Inscrição <?= esc($inscricao->numero_inscricao) ?><?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <p class="rotulo-secao mb-1"><?= esc($inscricao->numero_inscricao) ?></p>
    <h1 class="h3 mb-0"><?= esc($inscricao->nome_completo) ?></h1>
  </div>
  <div class="d-flex align-items-center gap-2">
    <?= view('components/badge_estado', ['estado' => $inscricao->status]) ?>
    <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/inscricoes') ?>">Voltar</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="cartao p-4">
      <h2 class="h6 mb-3">Dados do candidato</h2>
      <div class="row g-3">
        <div class="col-md-6"><div class="rotulo-secao">Nome</div><div class="fw-semibold"><?= esc($inscricao->nome_completo) ?></div></div>
        <div class="col-md-3"><div class="rotulo-secao">Classe</div><div><?= (int) $inscricao->classe_atual ?>.ª</div></div>
        <div class="col-md-3"><div class="rotulo-secao">Categoria</div><div><?= esc($inscricao->categoria ?? '—') ?></div></div>
        <div class="col-md-6"><div class="rotulo-secao">Escola</div><div><?= esc($inscricao->escola) ?></div></div>
        <div class="col-md-3"><div class="rotulo-secao">Província</div><div><?= esc($inscricao->provincia) ?></div></div>
        <div class="col-md-3"><div class="rotulo-secao">Inscrita em</div><div><?= esc(data_exibir($inscricao->data_inscricao, 'curta')) ?></div></div>
      </div>

      <?php if ($inscricao->status === 'rejeitada' && ! empty($inscricao->motivo_rejeicao)): ?>
        <div class="alert alert-danger mt-3 mb-0">
          <strong>Motivo da rejeição:</strong> <?= esc($inscricao->motivo_rejeicao) ?>
        </div>
      <?php endif ?>
    </div>
  </div>

  <div class="col-lg-4">
    <?php if ($inscricao->status === 'pendente' && auth()->user()->can('inscricoes.validar')): ?>
      <div class="cartao p-4">
        <h2 class="h6 mb-3">Decisão</h2>

        <form method="post" action="<?= site_url('admin/inscricoes/validar/' . $token) ?>" class="mb-3">
          <?= csrf_field() ?>
          <button class="btn btn-cns w-100" type="submit">
            <i class="bi bi-check-lg me-1"></i> Validar inscrição
          </button>
        </form>

        <hr>

        <form method="post" action="<?= site_url('admin/inscricoes/rejeitar/' . $token) ?>">
          <?= csrf_field() ?>
          <?= view('components/campo', [
              'nome' => 'motivo_rejeicao', 'rotulo' => 'Motivo da rejeição',
              'tipo' => 'textarea', 'linhas' => 3, 'obrigatorio' => true,
              'ajuda' => 'Será comunicado ao encarregado de educação.',
              'valor' => old('motivo_rejeicao'), 'erros' => session('erros'),
          ]) ?>
          <button class="btn btn-outline-danger w-100" type="submit">Rejeitar</button>
        </form>
      </div>
    <?php else: ?>
      <div class="cartao p-4">
        <h2 class="h6 mb-2">Estado</h2>
        <p class="texto-suave small mb-0">Esta inscrição já foi processada.</p>
      </div>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
