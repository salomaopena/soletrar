<?php
/** Pesquisa de candidatos inscritos: filtros + resultados + impressão/CSV. */
$qs = http_build_query(array_filter($filtros, static fn ($v) => $v !== '' && $v !== null));
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Candidatos<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
  <div>
    <h1 class="h3 mb-0">Candidatos inscritos</h1>
    <span class="texto-suave small"><strong><?= (int) $total ?></strong> resultado(s)</span>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-cns-contorno" target="_blank"
       href="<?= site_url('admin/candidatos/imprimir') . ($qs ? '?' . $qs : '') ?>">
      <i class="bi bi-printer me-1"></i> Imprimir lista
    </a>
    <a class="btn btn-cns-contorno"
       href="<?= site_url('admin/candidatos/exportar') . ($qs ? '?' . $qs : '') ?>">
      <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar (Excel)
    </a>
  </div>
</div>

<!-- ===================== FILTROS ===================== -->
<form method="get" action="<?= site_url('admin/candidatos') ?>" class="cartao p-3 mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label small" for="q">Pesquisar</label>
      <input class="form-control form-control-sm" id="q" name="q"
             value="<?= esc($filtros['q'], 'attr') ?>"
             placeholder="Nome, n.º de inscrição ou BI">
    </div>

    <?php
    $selects = [
        ['edicao_id',    'Edição',     $opcoes['edicoes']],
        ['provincia_id', 'Província',  $opcoes['provincias']],
        ['municipio_id', 'Município',  $opcoes['municipios']],
        ['escola_id',    'Escola',     $opcoes['escolas']],
        ['categoria_id', 'Categoria',  $opcoes['categorias']],
    ];
    ?>
    <?php foreach ($selects as [$nome, $rotulo, $lista]): ?>
      <div class="col-md-2">
        <label class="form-label small" for="<?= $nome ?>"><?= esc($rotulo) ?></label>
        <select class="form-select form-select-sm" id="<?= $nome ?>" name="<?= $nome ?>">
          <option value="">Todas</option>
          <?php foreach ($lista as $id => $texto): ?>
            <option value="<?= (int) $id ?>"
              <?= (string) $filtros[$nome] === (string) $id ? 'selected' : '' ?>>
              <?= esc($texto) ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>
    <?php endforeach ?>

    <div class="col-md-2">
      <label class="form-label small" for="classe">Classe</label>
      <select class="form-select form-select-sm" id="classe" name="classe">
        <option value="">Todas</option>
        <?php for ($i = 1; $i <= 8; $i++): ?>
          <option value="<?= $i ?>" <?= (string) $filtros['classe'] === (string) $i ? 'selected' : '' ?>>
            <?= $i ?>.ª
          </option>
        <?php endfor ?>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label small" for="genero">Género</label>
      <select class="form-select form-select-sm" id="genero" name="genero">
        <option value="">Todos</option>
        <?php foreach (['M' => 'Masculino','F' => 'Feminino'] as $k => $r): ?>
          <option value="<?= $k ?>" <?= $filtros['genero'] === $k ? 'selected' : '' ?>><?= $r ?></option>
        <?php endforeach ?>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label small" for="status">Estado</label>
      <select class="form-select form-select-sm" id="status" name="status">
        <option value="">Todos</option>
        <?php foreach (['pendente' => 'Pendente', 'validada' => 'Validada',
                        'rejeitada' => 'Rejeitada'] as $k => $r): ?>
          <option value="<?= $k ?>" <?= $filtros['status'] === $k ? 'selected' : '' ?>><?= $r ?></option>
        <?php endforeach ?>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label small" for="de">Inscrita de</label>
      <input class="form-control form-control-sm" type="date" id="de" name="de"
             value="<?= esc($filtros['de'] ?? '', 'attr') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small" for="ate">até</label>
      <input class="form-control form-control-sm" type="date" id="ate" name="ate"
             value="<?= esc($filtros['ate'] ?? '', 'attr') ?>">
    </div>

    <div class="col-md-4 d-flex gap-2">
      <button class="btn btn-cns btn-sm flex-fill" type="submit">
        <i class="bi bi-search me-1"></i> Pesquisar
      </button>
      <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/candidatos') ?>">Limpar</a>
    </div>
  </div>
</form>

<!-- ===================== RESULTADOS ===================== -->
<div class="cartao">
  <?php if (empty($candidatos)): ?>
    <?= view('components/estado_vazio', [
        'palavra'  => 'nada',
        'mensagem' => 'Nenhum candidato corresponde aos filtros aplicados.',
    ]) ?>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table tabela-cns table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>N.º</th><th>Candidato</th><th>Classe</th><th>Escola</th>
            <th>Província</th><th>Encarregado</th><th>Estado</th><th class="text-end">Ficha</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($candidatos as $c): ?>
            <tr>
              <td class="texto-suave small"><?= esc($c->numero_inscricao) ?></td>
              <td class="fw-semibold"><?= esc($c->nome_completo) ?></td>
              <td><?= (int) $c->classe_atual ?>.ª</td>
              <td class="texto-suave small"><?= esc($c->escola) ?></td>
              <td class="texto-suave small"><?= esc($c->provincia) ?></td>
              <td class="small">
                <?= esc($c->encarregado ?? '—') ?>
                <?php if ($c->telefone): ?>
                  <span class="texto-suave d-block"><?= esc(telefone_formatar($c->telefone)) ?></span>
                <?php endif ?>
              </td>
              <td><?= view('components/badge_estado', ['estado' => $c->status]) ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-cns-contorno"
                   href="<?= rota_segura('admin/candidatos/ficha', $c->candidato_id, 'candidato') ?>">
                  <i class="bi bi-person-lines-fill"></i>
                </a>
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
