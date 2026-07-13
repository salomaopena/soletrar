<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Eventos<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="d-flex justify-content-between align-items-end mb-4 gap-2">
  <h1 class="h3 mb-0">Eventos e rounds</h1>
  <a class="btn btn-cns" href="<?= site_url('admin/eventos/nova') ?>"><i class="bi bi-plus-lg me-1"></i> Novo evento</a>
</div>
<div class="cartao">
  <?php if (empty($eventos)): ?>
    <?= view('components/estado_vazio', [
        'palavra' => 'vazio', 'mensagem' => 'Ainda não há eventos agendados.',
        'acao' => ['url' => site_url('admin/eventos/nova'), 'rotulo' => 'Criar o primeiro evento']]) ?>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table tabela-cns table-hover align-middle mb-0">
        <thead><tr><th>Evento</th><th>Fase</th><th>Categoria</th><th>Data</th>
                   <th>Estado</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
          <?php foreach ($eventos as $e): ?>
            <tr>
              <td class="fw-semibold"><?= esc($e->nome) ?></td>
              <td class="texto-suave"><?= esc($e->fase) ?></td>
              <td class="texto-suave"><?= esc($e->categoria ?? '—') ?></td>
              <td class="small"><?= esc(data_exibir($e->data_evento, 'curta')) ?></td>
              <td><?= view('components/badge_estado', ['estado' => $e->status]) ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-cns-contorno"
                   href="<?= site_url('admin/eventos/editar/' . $e->id) ?>" title="Editar">
                  <i class="bi bi-pencil"></i>
                </a>
                <a class="btn btn-sm btn-cns-contorno" href="<?= site_url('admin/eventos/' . $e->id) ?>">Ver</a>
                <?php if ($e->status === 'em_curso'): ?>
                  <a class="btn btn-sm btn-cns" href="<?= site_url('admin/palco/' . $e->id) ?>">
                    <i class="bi bi-mic"></i> Palco
                  </a>
                <?php endif ?>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <div class="p-3"><?= $pager->links() ?></div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
