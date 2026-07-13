<?php
/**
 * Banco de palavras.
 *
 * REGRA CRÍTICA: só palavras VALIDADAS podem entrar no conjunto (pool)
 * de um evento. Uma palavra criada nasce por validar — daí o botão.
 */
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Banco de palavras<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
  <div>
    <h1 class="h3 mb-0">Banco de palavras</h1>
    <span class="texto-suave small">
      Só palavras <strong>validadas</strong> podem ser usadas em eventos.
    </span>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-cns-contorno" href="<?= site_url('admin/palavras/categorias') ?>">
      <i class="bi bi-bookmarks me-1"></i> Categorias
    </a>
    <a class="btn btn-cns" href="<?= site_url('admin/palavras/nova') ?>">
      <i class="bi bi-plus-lg me-1"></i> Nova palavra
    </a>
  </div>
</div>

<?php if (($contadores['por_validar'] ?? 0) > 0): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>
      Há <strong><?= (int) $contadores['por_validar'] ?></strong> palavra(s) por validar.
      Enquanto não forem validadas, <strong>não entram nos eventos</strong>.
    </span>
  </div>
<?php endif ?>

<!-- Filtros -->
<form method="get" class="d-flex flex-wrap gap-2 align-items-end mb-3">
  <ul class="nav nav-pills gap-1 mb-0">
    <?php foreach (['todas' => 'Todas', 'por_validar' => 'Por validar',
                    'validadas' => 'Validadas'] as $k => $r): ?>
      <li class="nav-item">
        <a class="nav-link <?= $estadoAtual === $k ? 'active' : '' ?>"
           href="<?= site_url('admin/palavras?estado=' . $k) ?>">
          <?= esc($r) ?>
          <span class="badge text-bg-light ms-1"><?= (int) ($contadores[$k] ?? 0) ?></span>
        </a>
      </li>
    <?php endforeach ?>
  </ul>
  <div class="ms-auto d-flex gap-2">
    <input type="hidden" name="estado" value="<?= esc($estadoAtual, 'attr') ?>">
    <input class="form-control form-control-sm" name="q" style="width:220px"
           value="<?= esc($termo, 'attr') ?>" placeholder="Procurar palavra…">
    <button class="btn btn-cns-contorno btn-sm" type="submit">
      <i class="bi bi-search"></i>
    </button>
  </div>
</form>

<form method="post" action="<?= site_url('admin/palavras/validar-varias') ?>">
  <?= csrf_field() ?>

  <div class="cartao">
    <?php if (empty($palavras)): ?>
      <?= view('components/estado_vazio', [
          'palavra'  => 'vazio',
          'mensagem' => 'O banco de palavras está vazio. Sem palavras validadas não há eventos.',
          'acao'     => ['url' => site_url('admin/palavras/nova'), 'rotulo' => 'Adicionar a primeira'],
      ]) ?>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table tabela-cns table-hover align-middle mb-0">
          <thead>
            <tr>
              <th style="width:32px">
                <input class="form-check-input" type="checkbox" id="marcarTodas"
                       aria-label="Marcar todas">
              </th>
              <th>Palavra</th><th>Silabação</th><th>Dificuldade</th>
              <th class="text-center">Classes</th><th class="text-center">Usos</th>
              <th class="text-center">Estado</th><th class="text-end">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($palavras as $p): ?>
              <tr>
                <td>
                  <?php if (! $p->validada): ?>
                    <input class="form-check-input marca" type="checkbox" name="ids[]"
                           value="<?= (int) $p->id ?>"
                           aria-label="Selecionar <?= esc($p->palavra, 'attr') ?>">
                  <?php endif ?>
                </td>
                <td class="fw-semibold"><?= esc($p->palavra) ?></td>
                <td class="texto-suave small"><?= esc($p->silabacao ?: '—') ?></td>
                <td class="small"><?= esc(lang('Concurso.dificuldade_' . $p->dificuldade)) ?></td>
                <td class="text-center small texto-suave">
                  <?= (int) $p->nivel_minimo_classe ?>.ª–<?= (int) $p->nivel_maximo_classe ?>.ª
                </td>
                <td class="text-center"><?= (int) $p->usada_em_concursos ?></td>
                <td class="text-center">
                  <?= $p->validada
                      ? '<span class="badge-estado badge-estado--validada">Validada</span>'
                      : '<span class="badge-estado badge-estado--pendente">Por validar</span>' ?>
                </td>
                <td class="text-end">
                  <a class="btn btn-sm btn-cns-contorno"
                     href="<?= site_url('admin/palavras/editar/' . $p->id) ?>">
                    <i class="bi bi-pencil"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>

      <?php if (auth()->user()->can('palavras.validar') && $estadoAtual !== 'validadas'): ?>
        <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
          <button class="btn btn-cns" type="submit">
            <i class="bi bi-check2-all me-1"></i> Validar selecionadas
          </button>
          <span class="texto-suave small">
            Validar torna a palavra elegível para os conjuntos dos eventos.
          </span>
        </div>
      <?php endif ?>

      <div class="p-3"><?= $pager->links() ?></div>
    <?php endif ?>
  </div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.getElementById('marcarTodas')?.addEventListener('change', (e) => {
  document.querySelectorAll('.marca').forEach(c => { c.checked = e.target.checked; });
});
</script>
<?= $this->endSection() ?>
