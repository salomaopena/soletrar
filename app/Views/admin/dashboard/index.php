<?php /* Dashboard administrativo. Dados: $stats, $inscricoesRecentes, $proximosEventos. */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Painel<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
  <div>
    <p class="rotulo-secao mb-1"><?= esc($stats['edicao_nome']) ?></p>
    <h1 class="h3 mb-0">Painel</h1>
  </div>
  <span class="texto-suave small">Atualizado <?= esc(data_exibir(utc_agora(), 'hora')) ?></span>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3"><?= view('components/cartao_stat', ['rotulo' => 'Inscrições recebidas', 'valor' => $stats['inscricoes'], 'cor' => 'azul']) ?></div>
  <div class="col-6 col-xl-3"><?= view('components/cartao_stat', ['rotulo' => 'Validadas', 'valor' => $stats['validadas'], 'cor' => 'verde']) ?></div>
  <div class="col-6 col-xl-3"><?= view('components/cartao_stat', ['rotulo' => 'Pendentes de validação', 'valor' => $stats['pendentes'], 'cor' => 'amarelo']) ?></div>
  <div class="col-6 col-xl-3"><?= view('components/cartao_stat', ['rotulo' => 'Escolas participantes', 'valor' => $stats['escolas'], 'cor' => 'roxo']) ?></div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="cartao p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Inscrições recentes</h2>
        <a class="small" href="<?= site_url('admin/inscricoes') ?>">Ver todas</a>
      </div>
      <?php if ($inscricoesRecentes === []): ?>
        <?= view('components/estado_vazio', ['palavra' => 'inicio', 'mensagem' => 'As inscrições aparecerão aqui assim que chegarem.']) ?>
      <?php else: ?>
        <table class="table tabela-cns table-hover align-middle mb-0">
          <thead><tr><th>Candidato</th><th>Província</th><th>Estado</th><th class="text-end">Recebida</th></tr></thead>
          <tbody>
            <?php foreach ($inscricoesRecentes as $i): ?>
              <tr>
                <td><a class="fw-semibold text-decoration-none"
                       href="<?= rota_segura('admin/inscricoes/ver', $i->id, 'inscricao') ?>"><?= esc($i->nome_completo) ?></a></td>
                <td class="texto-suave"><?= esc($i->provincia) ?></td>
                <td><?= view('components/badge_estado', ['estado' => $i->status]) ?></td>
                <td class="text-end texto-suave small"><?= esc(data_exibir($i->data_inscricao, 'curta')) ?></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      <?php endif ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="cartao p-4 h-100">
      <h2 class="h5 mb-3">Próximos eventos</h2>
      <?php foreach ($proximosEventos as $ev): ?>
        <div class="d-flex gap-3 py-2 border-bottom">
          <div class="text-center" style="min-width:3.2rem">
            <div class="fw-bold" style="font-family:var(--cns-fonte-display);color:var(--cns-marinho)">
              <?= esc(data_exibir($ev->data_evento, 'dia')) ?>
            </div>
            <div class="small texto-suave text-uppercase"><?= esc(data_exibir($ev->data_evento, 'mes')) ?></div>
          </div>
          <div>
            <div class="fw-semibold"><?= esc($ev->nome) ?></div>
            <div class="small texto-suave"><?= esc($ev->local ?? '') ?></div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
