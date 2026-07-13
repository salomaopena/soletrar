<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Fila de notificações<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<h1 class="h3 mb-1">Fila de notificações</h1>
<p class="texto-suave mb-4">Processada por <code>php spark notificacoes:processar</code> (cron a cada minuto).</p>

<ul class="nav nav-pills mb-3 gap-1">
  <?php foreach (['pendente' => 'Pendentes', 'enviada' => 'Enviadas', 'falhada' => 'Falhadas'] as $k => $r): ?>
    <li class="nav-item">
      <a class="nav-link <?= $estadoAtual === $k ? 'active' : '' ?>"
         href="<?= site_url('admin/notificacoes/fila?status=' . $k) ?>">
        <?= esc($r) ?> <span class="badge text-bg-light ms-1"><?= (int) ($contadores[$k] ?? 0) ?></span>
      </a>
    </li>
  <?php endforeach ?>
</ul>

<div class="cartao">
  <?php if (empty($mensagens)): ?>
    <?= view('components/estado_vazio', ['palavra' => 'zero', 'mensagem' => 'Nada na fila neste estado.']) ?>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table tabela-cns align-middle mb-0">
        <thead><tr><th>Canal</th><th>Destinatário</th><th>Assunto/Corpo</th>
                   <th class="text-center">Tent.</th><th>Erro</th><th class="text-end">Ação</th></tr></thead>
        <tbody>
          <?php foreach ($mensagens as $m): ?>
            <tr>
              <td><span class="badge text-bg-light"><?= esc($m->canal) ?></span></td>
              <td class="small"><?= esc($m->destinatario) ?></td>
              <td class="small text-truncate" style="max-width:280px">
                <?= esc($m->assunto ?: mb_substr($m->corpo, 0, 60)) ?>
              </td>
              <td class="text-center"><?= (int) $m->tentativas ?>/<?= (int) $m->max_tentativas ?></td>
              <td class="small text-danger text-truncate" style="max-width:200px"><?= esc($m->erro_ultimo ?? '') ?></td>
              <td class="text-end">
                <?php if ($m->status === 'falhada'): ?>
                  <form method="post" action="<?= site_url('admin/notificacoes/fila/' . $m->id . '/reenfileirar') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-cns-contorno" type="submit">Reenviar</button>
                  </form>
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
