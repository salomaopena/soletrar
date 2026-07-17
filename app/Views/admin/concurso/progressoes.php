<?php /** Progressões entre fases (quem se qualificou, como e por quem). */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Progressões<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
  <div>
    <h1 class="h3 mb-0">Progressões entre fases</h1>
    <span class="texto-suave small">
      Quem passou de fase, como se qualificou e quem homologou.
    </span>
  </div>
  <form method="get" class="d-flex gap-2 align-items-end">
    <div>
      <label class="form-label small mb-1" for="fase_id">Fase de destino</label>
      <select class="form-select form-select-sm" id="fase_id" name="fase_id" onchange="this.form.submit()">
        <option value="">Todas</option>
        <?php foreach ($fases as $f): ?>
          <option value="<?= (int) $f->id ?>" <?= (string) $faseAtual === (string) $f->id ? 'selected' : '' ?>>
            <?= esc($f->nome) ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>
  </form>
</div>

<div class="cartao">
  <?php if (empty($progressoes)): ?>
    <?= view('components/estado_vazio', [
        'palavra'  => 'zero',
        'mensagem' => 'Ainda não há progressões. Elas são criadas ao HOMOLOGAR um evento concluído.',
    ]) ?>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table tabela-cns align-middle mb-0">
        <thead><tr><th>Candidato</th><th>De</th><th>Para</th><th>Evento de origem</th>
                   <th class="text-center">Pos.</th><th>Tipo</th><th>Homologado por</th>
                   <th class="text-end">Ação</th></tr></thead>
        <tbody>
          <?php foreach ($progressoes as $p): ?>
            <tr>
              <td class="fw-semibold"><?= esc($p->nome_completo) ?>
                <span class="texto-suave small d-block"><?= esc($p->numero_inscricao) ?></span></td>
              <td class="texto-suave small"><?= esc($p->fase_origem) ?></td>
              <td class="small"><i class="bi bi-arrow-right me-1"></i><?= esc($p->fase_destino) ?></td>
              <td class="texto-suave small"><?= esc($p->evento ?? '—') ?></td>
              <td class="text-center"><?= $p->posicao_qualificacao ? (int) $p->posicao_qualificacao . '.º' : '—' ?></td>
              <td>
                <span class="badge text-bg-light"><?= esc(str_replace('_', ' ', $p->tipo)) ?></span>
                <?php if ($p->observacoes): ?>
                  <i class="bi bi-info-circle texto-suave ms-1"
                     title="<?= esc($p->observacoes, 'attr') ?>"></i>
                <?php endif ?>
              </td>
              <td class="texto-suave small"><?= esc($p->aprovada_por ?? '—') ?></td>
              <td class="text-end">
                <form method="post" action="<?= site_url('admin/progressoes/' . $p->id . '/remover') ?>"
                      onsubmit="return confirm('Remover esta progressão? Se o candidato já foi confirmado num evento da fase seguinte, tem de o remover à parte.')">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger" type="submit" title="Remover progressão">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
