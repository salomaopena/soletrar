<?php /** Painel de relatórios: funil por província, classe, género e escolas. */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Relatórios<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
  <h1 class="h3 mb-0">Relatórios</h1>
  <form method="get" class="d-flex gap-2 align-items-end">
    <div>
      <label class="form-label small mb-1" for="edicao_id">Edição</label>
      <select class="form-select form-select-sm" id="edicao_id" name="edicao_id" onchange="this.form.submit()">
        <?php foreach ($edicoes as $e): ?>
          <option value="<?= (int) $e->id ?>" <?= $edicaoId === (int) $e->id ? 'selected' : '' ?>>
            <?= esc($e->nome) ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>
    <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/relatorios/palavras?edicao_id=' . $edicaoId) ?>">
      Palavras difíceis
    </a>
  </form>
</div>

<!-- Resumo -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-2">
    <?= view('components/cartao_stat', ['rotulo' => 'Inscrições', 'valor' => $resumo['inscricoes'], 'cor' => 'azul']) ?>
  </div>
  <div class="col-6 col-xl-2">
    <?= view('components/cartao_stat', ['rotulo' => 'Validadas', 'valor' => $resumo['validadas'], 'cor' => 'verde']) ?>
  </div>
  <div class="col-6 col-xl-2">
    <?= view('components/cartao_stat', ['rotulo' => 'Pendentes', 'valor' => $resumo['pendentes'], 'cor' => 'amarelo']) ?>
  </div>
  <div class="col-6 col-xl-2">
    <?= view('components/cartao_stat', ['rotulo' => 'Rejeitadas', 'valor' => $resumo['rejeitadas'], 'cor' => 'rosa']) ?>
  </div>
  <div class="col-6 col-xl-2">
    <?= view('components/cartao_stat', ['rotulo' => 'Eventos', 'valor' => $resumo['eventos'], 'cor' => 'roxo']) ?></div>
  <div class="col-6 col-xl-2">
    <?= view('components/cartao_stat', ['rotulo' => 'Palavras validadas', 'valor' => $resumo['palavras_validadas'], 'cor' => 'laranja']) ?>
  </div>
</div>

<div class="row g-3">
  <!-- Funil por província -->
  <div class="col-lg-8">
    <div class="cartao p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 mb-0">Inscrições por província</h2>
        <a class="btn btn-sm btn-cns-contorno"
          href="<?= site_url('admin/relatorios/provincias/exportar?edicao_id=' . $edicaoId) ?>">
          <i class="bi bi-file-earmark-spreadsheet"></i> Exportar
        </a>
      </div>

      <div class="card">
        <div class="card-body p-2">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  <th>Província</th>
                  <th class="text-center">Escolas</th>
                  <th class="text-center">Inscrições</th>
                  <th class="text-center">Validadas</th>
                  <th class="text-center">Pendentes</th>
                  <th>Taxa de validação</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($provincias as $p): ?>
                  <?php
                  $insc = (int) $p['inscricoes'];
                  $val = (int) $p['validadas'];
                  $taxa = $insc > 0 ? round(100 * $val / $insc) : 0;
                  ?>
                  <tr>
                    <td class="fw-semibold"><?= esc($p['nome']) ?></td>
                    <td class="text-center"><?= (int) $p['escolas_participantes'] ?></td>
                    <td class="text-center fw-semibold"><?= $insc ?></td>
                    <td class="text-center"><?= $val ?></td>
                    <td class="text-center"><?= (int) $p['pendentes'] ?></td>
                    <td style="min-width:140px">
                      <div class="progress" style="height:6px">
                        <div class="progress-bar" role="progressbar"
                          style="width: <?= $taxa ?>%; background: var(--cns-verde-agua)" aria-valuenow="<?= $taxa ?>"
                          aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                      <span class="texto-suave small"><?= $taxa ?>%</span>
                    </td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <!-- Por classe -->
    <div class="cartao p-4 mb-3">
      <h2 class="h6 mb-3">Por classe</h2>
      <?php $maxC = 0;
      foreach ($porClasse as $c) {
        $maxC = max($maxC, (int) $c->total);
      } ?>
      <?php foreach ($porClasse as $c): ?>
        <div class="mb-2">
          <div class="d-flex justify-content-between small">
            <span><?= (int) $c->classe ?>.ª classe</span>
            <strong><?= (int) $c->total ?></strong>
          </div>
          <div class="progress" style="height:5px">
            <div class="progress-bar" style="width: <?= $maxC ? round(100 * $c->total / $maxC) : 0 ?>%;
                 background: var(--cns-azul)"></div>
          </div>
        </div>
      <?php endforeach ?>
      <?php if (empty($porClasse)): ?>
        <p class="texto-suave small mb-0">Sem dados.</p><?php endif ?>
    </div>

    <!-- Por género -->
    <div class="cartao p-4 mb-3">
      <h2 class="h6 mb-3">Por género</h2>
      <?php foreach ($porGenero as $g): ?>
        <div class="d-flex justify-content-between small py-1">
          <span><?= esc(ucfirst($g->genero)) ?></span>
          <strong><?= (int) $g->total ?></strong>
        </div>
      <?php endforeach ?>
      <?php if (empty($porGenero)): ?>
        <p class="texto-suave small mb-0">Sem dados.</p><?php endif ?>
    </div>

    <!-- Top escolas -->
    <div class="cartao p-4">
      <h2 class="h6 mb-3">Escolas com mais inscrições</h2>
      <ol class="ps-3 mb-0 small">
        <?php foreach ($porEscola as $e): ?>
          <li class="mb-1">
            <?= esc($e->escola) ?>
            <span class="texto-suave">(<?= esc($e->provincia) ?>)</span> —
            <strong><?= (int) $e->total ?></strong>
          </li>
        <?php endforeach ?>
      </ol>
      <?php if (empty($porEscola)): ?>
        <p class="texto-suave small mb-0">Sem dados.</p><?php endif ?>
    </div>
  </div>
</div>
<?= $this->endSection() ?>