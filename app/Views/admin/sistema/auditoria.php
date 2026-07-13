<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Auditoria<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<h1 class="h3 mb-1">Auditoria</h1>
<p class="texto-suave mb-4">Registo imutável de todas as ações sensíveis do sistema.</p>
<div class="cartao">
  <?php if (empty($registos)): ?>
    <?= view('components/estado_vazio', ['palavra' => 'zero', 'mensagem' => 'Sem registos de auditoria.']) ?>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table tabela-cns align-middle mb-0">
        <thead><tr><th>Quando</th><th>Utilizador</th><th>Ação</th><th>Entidade</th><th>IP</th></tr></thead>
        <tbody>
          <?php foreach ($registos as $r): ?>
            <tr>
              <td class="texto-suave small"><?= esc(data_exibir($r->created_at, 'curta_hora')) ?></td>
              <td><?= esc($r->username ?? 'sistema') ?></td>
              <td><span class="badge text-bg-light"><?= esc($r->acao) ?></span></td>
              <td class="small"><?= esc($r->entidade) ?> <?= $r->entidade_id ? '#' . (int) $r->entidade_id : '' ?></td>
              <td class="texto-suave small"><?= esc($r->ip_address ?? '') ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <div class="p-3"><?= $pager->links() ?></div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
