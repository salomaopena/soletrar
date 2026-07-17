<?php
/**
 * Atribuição de prémios de um evento.
 * QUANDO: depois de o evento estar concluído (classificação calculada).
 */
$pendentes = 0;
foreach ($candidatos as $c) {
    if (! $c->ja_atribuido && $c->participacao_id !== null) { $pendentes++; }
}
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Prémios<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <p class="rotulo-secao mb-1"><?= esc($evento->nome) ?></p>
    <h1 class="h3 mb-0">Prémios</h1>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-cns-contorno btn-sm" target="_blank"
       href="<?= site_url('admin/eventos/' . $evento->id . '/premios/imprimir') ?>">
      <i class="bi bi-printer"></i> Lista de premiados
    </a>
    <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/eventos/' . $evento->id) ?>">Voltar</a>
  </div>
</div>

<?php if (empty($candidatos)): ?>
  <div class="cartao p-5">
    <?= view('components/estado_vazio', [
        'palavra'  => 'zero',
        'mensagem' => 'Não há prémios configurados para esta categoria/fase/edição.',
        'acao'     => auth()->user()->can('sistema.configuracoes.gerir')
            ? ['url' => site_url('admin/parcerias/premios/nova'), 'rotulo' => 'Configurar um prémio']
            : null,
    ]) ?>
  </div>
<?php else: ?>

  <?php if ($pendentes > 0): ?>
    <form method="post" action="<?= site_url('admin/eventos/' . $evento->id . '/premios/atribuir') ?>" class="mb-3">
      <?= csrf_field() ?>
      <button class="btn btn-cns" type="submit">
        <i class="bi bi-award me-1"></i> Atribuir <?= $pendentes ?> prémio(s) pendente(s)
      </button>
    </form>
  <?php endif ?>

  <div class="cartao">
    <div class="table-responsive">
      <table class="table tabela-cns align-middle mb-0">
        <thead>
          <tr>
            <th class="text-center">Posição</th><th>Prémio</th><th>Patrocinador</th>
            <th>Vencedor</th><th>Escola</th><th class="text-center">Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($candidatos as $c): ?>
            <tr>
              <td class="text-center">
                <?php if ($c->posicao <= 3): ?>
                  <span class="medalha medalha--<?= (int) $c->posicao ?>"><?= (int) $c->posicao ?></span>
                <?php else: ?>
                  <?= (int) $c->posicao ?>.º
                <?php endif ?>
              </td>
              <td class="fw-semibold">
                <?= esc($c->premio_nome) ?>
                <span class="texto-suave small d-block"><?= esc(ucfirst($c->tipo)) ?>
                  <?php if ($c->valor_monetario): ?>
                    · <?= esc(moeda_aoa((float) $c->valor_monetario)) ?>
                  <?php endif ?>
                </span>
              </td>
              <td class="texto-suave small"><?= esc($c->patrocinador ?? '—') ?></td>
              <td>
                <?php if ($c->nome_completo): ?>
                  <?= esc($c->nome_completo) ?>
                  <span class="texto-suave small d-block">#<?= esc($c->numero_concorrente) ?></span>
                <?php else: ?>
                  <span class="text-warning small">Ninguém nesta posição ainda</span>
                <?php endif ?>
              </td>
              <td class="texto-suave small"><?= esc($c->escola ?? '—') ?></td>
              <td class="text-center">
                <?php if ($c->participacao_id === null): ?>
                  <span class="badge-estado badge-estado--rascunho">Sem vencedor</span>
                <?php elseif ($c->ja_atribuido): ?>
                  <span class="badge-estado badge-estado--validada">Atribuído</span>
                <?php else: ?>
                  <span class="badge-estado badge-estado--pendente">Pendente</span>
                <?php endif ?>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif ?>
<?= $this->endSection() ?>
