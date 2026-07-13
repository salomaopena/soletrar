<?php /** Registo de envios de e-mail e SMS. */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Registo de envios<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<h1 class="h3 mb-1">Registo de envios</h1>
<p class="texto-suave mb-4">O que foi realmente enviado, a quem, e com que resultado.</p>

<?php if ($canal === 'sms' && $custo): ?>
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <?= view('components/cartao_stat', ['rotulo' => 'SMS (últimos 30 dias)',
          'valor' => (int) $custo->mensagens, 'cor' => 'azul']) ?>
    </div>
    <div class="col-6 col-lg-3">
      <?= view('components/cartao_stat', ['rotulo' => 'Segmentos faturados',
          'valor' => (int) $custo->partes, 'cor' => 'amarelo']) ?>
    </div>
    <div class="col-12 col-lg-3">
      <div class="cartao stat-card h-100">
        <div class="faixa" style="background:var(--cns-verde)"></div>
        <div class="valor"><?= esc(moeda_aoa((float) ($custo->total ?? 0))) ?></div>
        <div class="rotulo">Custo (últimos 30 dias)</div>
      </div>
    </div>
  </div>
<?php endif ?>

<ul class="nav nav-pills gap-1 mb-3">
  <?php foreach (['sms' => 'SMS', 'email' => 'E-mail'] as $k => $r): ?>
    <li class="nav-item">
      <a class="nav-link <?= $canal === $k ? 'active' : '' ?>"
         href="<?= site_url('admin/notificacoes/logs?canal=' . $k) ?>"><?= esc($r) ?></a>
    </li>
  <?php endforeach ?>
</ul>

<div class="cartao">
  <?php if (empty($logs)): ?>
    <?= view('components/estado_vazio', ['palavra' => 'zero',
        'mensagem' => 'Ainda não há envios registados.']) ?>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table tabela-cns align-middle mb-0">
        <thead>
          <tr>
            <th>Destinatário</th>
            <th><?= $canal === 'email' ? 'Assunto' : 'Mensagem' ?></th>
            <?php if ($canal === 'sms'): ?>
              <th class="text-center">Partes</th><th class="text-end">Custo</th>
            <?php endif ?>
            <th class="text-center">Estado</th><th class="text-end">Quando</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $l): ?>
            <tr>
              <td class="small"><?= esc($canal === 'email' ? $l->destinatario : $l->telefone) ?></td>
              <td class="small text-truncate" style="max-width:340px">
                <?= esc($canal === 'email' ? $l->assunto : $l->mensagem) ?>
                <?php if ($l->erro): ?>
                  <span class="text-danger d-block small"><?= esc($l->erro) ?></span>
                <?php endif ?>
              </td>
              <?php if ($canal === 'sms'): ?>
                <td class="text-center"><?= (int) $l->partes ?></td>
                <td class="text-end small"><?= esc(moeda_aoa((float) $l->custo)) ?></td>
              <?php endif ?>
              <td class="text-center">
                <?php $cor = in_array($l->status, ['enviado', 'entregue'], true) ? 'validada' : 'rejeitada'; ?>
                <span class="badge-estado badge-estado--<?= $cor ?>"><?= esc($l->status) ?></span>
              </td>
              <td class="text-end texto-suave small"><?= esc(data_exibir($l->created_at, 'curta_hora')) ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <div class="p-3"><?= $pager->links() ?></div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
