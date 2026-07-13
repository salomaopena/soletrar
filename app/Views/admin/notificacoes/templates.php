<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Modelos de mensagem<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<h1 class="h3 mb-1">Modelos de mensagem</h1>
<p class="texto-suave mb-4">
  Desativar um modelo <strong>desliga esse canal</strong> para o evento correspondente.
</p>

<div class="cartao">
  <div class="table-responsive">
    <table class="table tabela-cns align-middle mb-0">
      <thead><tr><th>Código</th><th>Nome</th><th>Canal</th>
                 <th class="text-center">Ativo</th><th class="text-end">Ações</th></tr></thead>
      <tbody>
        <?php foreach ($templates as $t): ?>
          <tr>
            <td class="texto-suave small"><code><?= esc($t->codigo) ?></code></td>
            <td class="fw-semibold"><?= esc($t->nome) ?></td>
            <td><span class="badge text-bg-light"><?= esc($t->canal) ?></span></td>
            <td class="text-center">
              <?= $t->ativo
                  ? '<i class="bi bi-check-circle-fill text-success"></i>'
                  : '<i class="bi bi-dash-circle text-muted"></i>' ?>
            </td>
            <td class="text-end">
              <form method="post" class="d-inline"
                    action="<?= site_url('admin/notificacoes/templates/' . $t->id . '/alternar') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-cns-contorno" type="submit">
                  <?= $t->ativo ? 'Desativar' : 'Ativar' ?>
                </button>
              </form>
              <a class="btn btn-sm btn-cns-contorno"
                 href="<?= site_url('admin/notificacoes/templates/editar/' . $t->id) ?>">
                <i class="bi bi-pencil"></i>
              </a>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <?php if (empty($templates)): ?>
    <?= view('components/estado_vazio', ['palavra' => 'zero',
        'mensagem' => 'Sem modelos. Os principais vêm no seed do esquema.']) ?>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
