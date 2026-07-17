<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Eventos<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="d-flex justify-content-between align-items-end mb-4 gap-2">
  <h1 class="h3 mb-0">Eventos e rounds</h1>
  <a class="btn btn-cns" href="<?= site_url('admin/eventos/nova') ?>"><i class="bi bi-plus-lg me-1"></i> Novo evento</a>
</div>

<?php if (!empty($duplicados)): ?>
  <div class="alert alert-warning d-flex align-items-start gap-2">
    <i class="bi bi-exclamation-triangle-fill mt-1"></i>
    <div>
      <strong>Eventos duplicados (criados antes desta proteção existir)</strong> — os eventos
      marcados com <i class="bi bi-exclamation-triangle-fill text-warning"></i> abaixo partilham
      a mesma fase, categoria e escola/província com outro evento ativo. Isto gera duas
      classificações paralelas para os mesmos candidatos. <strong>Não é mais possível criar
        um novo evento duplicado</strong> — o sistema bloqueia isso automaticamente — mas estes
      já existiam. Cancele o que estiver a mais (editar → estado → Cancelado).
    </div>
  </div>
<?php endif ?>

<div class="cartao">
  <?php if (empty($eventos)): ?>
    <?= view('components/estado_vazio', [
      'palavra' => 'vazio',
      'mensagem' => 'Ainda não há eventos agendados.',
      'acao' => ['url' => site_url('admin/eventos/nova'), 'rotulo' => 'Criar o primeiro evento']
    ]) ?>
  <?php else: ?>
    <div class="card">
      <div class="card-body p-2">
        <div class="table-responsive">
          <table class="table tabela-cns table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Evento</th>
                <th>Fase</th>
                <th>Categoria</th>
                <th>Data</th>
                <th>Estado</th>
                <th class="text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($eventos as $e): ?>
                <tr class="<?= in_array($e->id, $duplicados, true) ? 'table-warning' : '' ?>">
                  <td class="fw-semibold">
                    <?php if (in_array($e->id, $duplicados, true)): ?>
                      <i class="bi bi-exclamation-triangle-fill text-warning me-1"
                        title="Partilha fase+categoria+escola/província com outro evento ativo"></i>
                    <?php endif ?>
                    <?= esc($e->nome) ?>
                  </td>
                  <td class="texto-suave"><?= esc($e->fase) ?></td>
                  <td class="texto-suave"><?= esc($e->categoria ?? '—') ?></td>
                  <td class="small"><?= esc(data_exibir($e->data_evento, 'curta')) ?></td>
                  <td><?= view('components/badge_estado', ['estado' => $e->status]) ?></td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-cns-contorno" href="<?= site_url('admin/eventos/editar/' . $e->id) ?>"
                      title="Editar">
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
      </div>
    </div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>