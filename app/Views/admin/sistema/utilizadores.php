<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Utilizadores<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="d-flex justify-content-between align-items-end mb-4 gap-2">
  <h1 class="h3 mb-0">Utilizadores</h1>
  <a class="btn btn-cns" href="<?= site_url('admin/sistema/utilizadores/nova') ?>">
    <i class="bi bi-person-plus me-1"></i> Adicionar utilizador
  </a>
</div>

<div class="cartao">
  <div class="table-responsive">
    <table class="table tabela-cns align-middle mb-0">
      <thead><tr><th>Utilizador</th><th>Nome</th><th>E-mail</th><th>Grupos</th>
                 <th class="text-center">Ativo</th><th class="text-end">Ações</th></tr></thead>
      <tbody>
        <?php foreach ($utilizadores as $u): ?>
          <tr>
            <td class="fw-semibold"><?= esc($u->username) ?></td>
            <td><?= esc($u->nome_completo ?? '—') ?></td>
            <td class="texto-suave small"><?= esc($u->email ?? '—') ?></td>
            <td>
              <?php foreach (array_filter(explode(',', (string) $u->grupos)) as $g): ?>
                <span class="badge text-bg-light"><?= esc($g) ?></span>
              <?php endforeach ?>
            </td>
            <td class="text-center">
              <?= $u->active
                  ? '<i class="bi bi-check-circle-fill text-success"></i>'
                  : '<i class="bi bi-dash-circle text-muted"></i>' ?>
            </td>
            <td class="text-end">
              <div class="d-flex gap-1 justify-content-end">
                <a class="btn btn-sm btn-cns-contorno"
                   href="<?= site_url('admin/sistema/utilizadores/' . $u->id . '/atribuicoes') ?>"
                   title="Âmbito territorial">
                  <i class="bi bi-geo-alt"></i> Atribuições
                </a>
                <form method="post"
                      action="<?= site_url('admin/sistema/utilizadores/' . $u->id . '/estado') ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-cns-contorno" type="submit">
                    <?= $u->active ? 'Desativar' : 'Ativar' ?>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>
<?= $this->endSection() ?>
