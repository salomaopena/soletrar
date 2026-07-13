<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Notificações<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">Notificações</h1>
  <form method="post" action="<?= site_url('admin/notificacoes/todas-lidas') ?>">
    <?= csrf_field() ?>
    <button class="btn btn-cns-contorno btn-sm" type="submit">Marcar todas como lidas</button>
  </form>
</div>

<div class="cartao">
  <?php if (empty($notificacoes)): ?>
    <?= view('components/estado_vazio', ['palavra' => 'zero', 'mensagem' => 'Sem notificações.']) ?>
  <?php else: ?>
    <ul class="list-group list-group-flush">
      <?php foreach ($notificacoes as $n): ?>
        <li class="list-group-item d-flex justify-content-between align-items-start gap-3
                   <?= $n->lida ? 'opacity-50' : '' ?>">
          <div>
            <div class="fw-semibold"><?= esc($n->titulo) ?></div>
            <div class="small texto-suave"><?= esc($n->mensagem) ?></div>
            <div class="small texto-suave mt-1"><?= esc(data_exibir($n->created_at, 'curta_hora')) ?></div>
          </div>
          <div class="d-flex gap-1">
            <?php if ($n->link): ?>
              <a class="btn btn-sm btn-cns-contorno" href="<?= esc($n->link, 'attr') ?>">Abrir</a>
            <?php endif ?>
            <?php if (! $n->lida): ?>
              <form method="post" action="<?= site_url('admin/notificacoes/' . $n->id . '/lida') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-cns-contorno" type="submit">
                  <i class="bi bi-check"></i>
                </button>
              </form>
            <?php endif ?>
          </div>
        </li>
      <?php endforeach ?>
    </ul>
    <div class="p-3"><?= $pager->links() ?></div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
