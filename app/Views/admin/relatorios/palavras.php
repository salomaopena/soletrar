<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Palavras mais difíceis<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="d-flex justify-content-between align-items-end mb-4">
  <div>
    <h1 class="h3 mb-0">Palavras mais difíceis</h1>
    <span class="texto-suave small">Taxa de acerto por palavra (mínimo 3 utilizações).</span>
  </div>
  <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/relatorios') ?>">Voltar</a>
</div>

<div class="cartao">
  <?php if (empty($palavras)): ?>
    <?= view('components/estado_vazio', [
        'palavra'  => 'zero',
        'mensagem' => 'Ainda não há tentativas suficientes para calcular taxas de acerto.',
    ]) ?>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table tabela-cns align-middle mb-0">
        <thead><tr><th>Palavra</th><th>Dificuldade</th>
                   <th class="text-center">Vezes usada</th><th>Taxa de acerto</th></tr></thead>
        <tbody>
          <?php foreach ($palavras as $p): ?>
            <tr>
              <td class="fw-semibold"><?= esc($p['palavra']) ?></td>
              <td class="texto-suave small"><?= esc(lang('Concurso.dificuldade_' . $p['dificuldade'])) ?></td>
              <td class="text-center"><?= (int) $p['vezes_usada'] ?></td>
              <td style="min-width:160px">
                <div class="progress" style="height:6px">
                  <div class="progress-bar" style="width: <?= (float) $p['taxa_acerto'] ?>%;
                       background: <?= $p['taxa_acerto'] < 40 ? 'var(--cns-perigo)' : 'var(--cns-verde)' ?>"></div>
                </div>
                <span class="texto-suave small"><?= (float) $p['taxa_acerto'] ?>%</span>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
