<?php
/**
 * Sala de controlo do evento: júri, participantes, pool de palavras e ações.
 * Alimentado por EventosController::ver().
 */
$papeis = [
    'presidente'    => 'Presidente',
    'jurado'        => 'Jurado',
    'pronunciador'  => 'Pronunciador',
    'juiz_apelacao' => 'Juiz de apelação',
    'cronometrista' => 'Cronometrista',
    'secretario'    => 'Secretário',
];
$temPresidente   = false;
$temPronunciador = false;
foreach ($juri as $j) {
    if ($j->papel === 'presidente')   { $temPresidente = true; }
    if ($j->papel === 'pronunciador') { $temPronunciador = true; }
}
$presentes = 0;
foreach ($participantes as $p) { if ($p->presenca === 'presente') { $presentes++; } }
$totalPool = array_sum($poolRestante);
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= esc($evento->nome) ?><?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <p class="rotulo-secao mb-1"><?= esc($evento->fase) ?> · <?= esc($evento->categoria ?? '') ?></p>
    <h1 class="h3 mb-0"><?= esc($evento->nome) ?></h1>
    <span class="texto-suave"><?= esc(data_exibir($evento->data_evento, 'longa')) ?></span>
  </div>
  <div class="d-flex align-items-center gap-2">
    <?= view('components/badge_estado', ['estado' => $evento->status]) ?>

    <?php if ($evento->status === 'agendado'): ?>
      <form method="post" action="<?= site_url('admin/eventos/' . $evento->id . '/iniciar') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-cns" type="submit"
                <?= (! $temPresidente || ! $temPronunciador || $totalPool === 0 || $presentes < 2) ? 'disabled' : '' ?>>
          <i class="bi bi-play-fill me-1"></i> Iniciar evento
        </button>
      </form>
    <?php elseif ($evento->status === 'em_curso'): ?>
      <a class="btn btn-cns" href="<?= site_url('admin/palco/' . $evento->id) ?>">
        <i class="bi bi-mic me-1"></i> Abrir o palco
      </a>
    <?php elseif ($evento->status === 'concluido' && $jaHomologado): ?>
      <span class="badge-estado badge-estado--validada">
        <i class="bi bi-patch-check-fill me-1"></i> Já homologado
      </span>
    <?php elseif ($evento->status === 'concluido' && auth()->user()->can('concurso.resultados.homologar')): ?>
      <form method="post" action="<?= site_url('admin/eventos/' . $evento->id . '/homologar') ?>"
            onsubmit="return confirm('Homologar sela os resultados, publica-os e apura a progressão. Só se faz uma vez. Continuar?')">
        <?= csrf_field() ?>
        <button class="btn btn-cns" type="submit"><i class="bi bi-check2-square me-1"></i> Homologar</button>
      </form>
    <?php endif ?>
    <?php if ($evento->status === 'concluido'): ?>
      <a class="btn btn-cns-contorno" href="<?= site_url('admin/eventos/' . $evento->id . '/premios') ?>">
        <i class="bi bi-award me-1"></i> Prémios
      </a>
      <form method="post" action="<?= site_url('admin/eventos/' . $evento->id . '/recalcular') ?>"
            onsubmit="return confirm('Recalcular substitui as posições finais atuais. Continuar?')">
        <?= csrf_field() ?>
        <button class="btn btn-cns-contorno" type="submit" title="Reaplica os critérios de classificação mais recentes">
          <i class="bi bi-arrow-clockwise me-1"></i> Recalcular classificação
        </button>
      </form>
    <?php endif ?>
  </div>
</div>

<!-- Checklist de prontidão -->
<?php if ($evento->status === 'agendado'): ?>
  <div class="cartao p-3 mb-3">
    <p class="rotulo-secao mb-2">Prontidão para iniciar</p>
    <div class="d-flex flex-wrap gap-3 small">
      <span><?= $temPresidente ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>' ?> Presidente do júri</span>
      <span><?= $temPronunciador ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>' ?> Pronunciador</span>
      <span><?= $totalPool > 0 ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>' ?> Palavras no conjunto (<?= (int) $totalPool ?>)</span>
      <span><?= $presentes >= 2 ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>' ?> Pelo menos 2 presentes (<?= $presentes ?>)</span>
    </div>
  </div>
<?php endif ?>

<div class="row g-3">

  <!-- ================= PARTICIPANTES ================= -->
  <div class="col-lg-7">
    <div class="cartao p-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h2 class="h6 mb-0">Participantes (<?= count($participantes) ?>)</h2>
        <div class="d-flex gap-2">
          <a class="btn btn-sm btn-cns-contorno"
             href="<?= site_url('admin/eventos/' . $evento->id . '/lista') ?>" target="_blank">
            <i class="bi bi-printer"></i> Imprimir lista
          </a>
          <a class="btn btn-sm btn-cns-contorno"
             href="<?= site_url('admin/eventos/' . $evento->id . '/rounds') ?>">
            <i class="bi bi-list-ol"></i> Rounds
          </a>
          <a class="btn btn-sm btn-cns-contorno"
             href="<?= site_url('admin/eventos/' . $evento->id . '/tentativas') ?>">
            <i class="bi bi-clock-history"></i> Tentativas
          </a>
          <form method="post" action="<?= site_url('admin/eventos/' . $evento->id . '/participantes') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-cns" type="submit">
              <i class="bi bi-people me-1"></i> Confirmar elegíveis
            </button>
          </form>
        </div>
      </div>

      <?php if (empty($participantes)): ?>
        <p class="texto-suave small mb-0">
          Sem participantes. Use <strong>Confirmar elegíveis</strong> para trazer as inscrições
          validadas (fase escolar) ou os qualificados da fase anterior.
        </p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table tabela-cns align-middle mb-0">
            <thead><tr><th>#</th><th>Candidato</th><th>Escola</th><th>Presença</th></tr></thead>
            <tbody>
              <?php foreach ($participantes as $p): ?>
                <tr>
                  <td class="texto-suave"><?= esc($p->numero_concorrente) ?></td>
                  <td class="fw-semibold"><?= esc($p->nome_completo) ?>
                    <span class="texto-suave small">(<?= (int) $p->classe_atual ?>.ª)</span>
                  </td>
                  <td class="texto-suave small"><?= esc($p->escola) ?></td>
                  <td>
                    <form method="post" class="d-flex gap-1"
                          action="<?= site_url('admin/eventos/' . $evento->id . '/presenca/' . $p->id) ?>">
                      <?= csrf_field() ?>
                      <select class="form-select form-select-sm" name="presenca"
                              onchange="this.form.submit()" style="width:auto">
                        <?php foreach (['confirmada' => 'Confirmada', 'presente' => 'Presente',
                                        'ausente' => 'Ausente', 'desistiu' => 'Desistiu'] as $k => $r): ?>
                          <option value="<?= $k ?>" <?= $p->presenca === $k ? 'selected' : '' ?>><?= $r ?></option>
                        <?php endforeach ?>
                      </select>
                    </form>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      <?php endif ?>
    </div>
  </div>

  <div class="col-lg-5">

    <!-- ================= JÚRI ================= -->
    <div class="cartao p-4 mb-3">
      <h2 class="h6 mb-3">Júri</h2>

      <?php if (empty($juri)): ?>
        <p class="text-warning small">Sem júri. É obrigatório um <strong>presidente</strong> e um
           <strong>pronunciador</strong>.</p>
      <?php else: ?>
        <ul class="list-group list-group-flush mb-3">
          <?php foreach ($juri as $j): ?>
            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
              <span><?= esc($j->username) ?>
                <span class="badge text-bg-light ms-1"><?= esc($papeis[$j->papel] ?? $j->papel) ?></span>
              </span>
              <form method="post"
                    action="<?= site_url('admin/eventos/' . $evento->id . '/juri/' . $j->id . '/remover') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Remover">
                  <i class="bi bi-x"></i>
                </button>
              </form>
            </li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>

      <form method="post" action="<?= site_url('admin/eventos/' . $evento->id . '/juri') ?>"
            class="border-top pt-3">
        <?= csrf_field() ?>
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label small" for="user_id">Utilizador</label>
            <select class="form-select form-select-sm" id="user_id" name="user_id" required>
              <option value="">—</option>
              <?php foreach ($candidatosJuri as $u): ?>
                <option value="<?= (int) $u->id ?>"><?= esc($u->username) ?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label small" for="papel">Papel</label>
            <select class="form-select form-select-sm" id="papel" name="papel" required>
              <?php foreach ($papeis as $k => $r): ?>
                <option value="<?= $k ?>"><?= esc($r) ?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="col-12">
            <button class="btn btn-cns btn-sm w-100" type="submit">
              <i class="bi bi-plus-lg me-1"></i> Atribuir ao júri
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- ================= POOL DE PALAVRAS ================= -->
    <div class="cartao p-4">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <h2 class="h6 mb-0">Conjunto de palavras (pool)</h2>
        <a class="small" href="<?= site_url('admin/eventos/' . $evento->id . '/pool') ?>">Ver conteúdo</a>
      </div>
      <p class="texto-suave small mb-3">
        Total no conjunto: <strong><?= (int) $poolTotal ?></strong> ·
        por usar: <strong><?= (int) array_sum($poolRestante) ?></strong>
      </p>

      <?php if (! empty($poolRestante)): ?>
        <ul class="list-group list-group-flush mb-3">
          <?php foreach ($poolRestante as $dif => $n): ?>
            <li class="list-group-item px-0 d-flex justify-content-between">
              <span class="small"><?= esc(lang('Concurso.dificuldade_' . $dif)) ?></span>
              <strong><?= (int) $n ?></strong>
            </li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>

      <form method="post" action="<?= site_url('admin/eventos/' . $evento->id . '/pool') ?>"
            class="border-top pt-3">
        <?= csrf_field() ?>
        <p class="rotulo-secao mb-2">Adicionar palavras ao conjunto</p>
        <div class="row g-2">
          <?php foreach (['muito_facil' => 'Muito fácil', 'facil' => 'Fácil', 'media' => 'Média',
                          'dificil' => 'Difícil', 'muito_dificil' => 'Muito difícil'] as $k => $r): ?>
            <div class="col-6">
              <label class="form-label small" for="pool-<?= $k ?>"><?= esc($r) ?></label>
              <input class="form-control form-control-sm" type="number" min="0"
                     id="pool-<?= $k ?>" name="<?= $k ?>" value="0">
            </div>
          <?php endforeach ?>
          <div class="col-12 mt-2">
            <button class="btn btn-cns btn-sm w-100" type="submit">
              <i class="bi bi-journal-plus me-1"></i> Montar conjunto
            </button>
          </div>
        </div>
        <p class="form-text mt-2 mb-0">
          Só entram palavras <strong>validadas</strong>, adequadas à classe da categoria e
          <strong>ainda não usadas nesta edição</strong>.
        </p>
      </form>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
