<?php
/**
 * Conjunto (pool) de palavras do evento.
 *
 * Duas formas de encher: automática (por dificuldade, na página do evento)
 * ou MANUAL (escolhendo palavras concretas aqui).
 * Só entram palavras VALIDADAS e adequadas à classe da categoria.
 */
$porUsar = 0;
foreach ($palavras as $p) { if (! $p->usada) { $porUsar++; } }
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Conjunto de palavras<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <p class="rotulo-secao mb-1"><?= esc($evento->nome) ?></p>
    <h1 class="h3 mb-0">Conjunto de palavras</h1>
    <span class="texto-suave small">
      <?= count($palavras) ?> no conjunto · <strong><?= $porUsar ?></strong> por usar
    </span>
  </div>
  <div class="d-flex gap-2">
    <?php if ($porUsar > 0): ?>
      <form method="post" action="<?= site_url('admin/eventos/' . $evento->id . '/pool/limpar') ?>"
            onsubmit="return confirm('Remover todas as palavras ainda não usadas do conjunto?')">
        <?= csrf_field() ?>
        <button class="btn btn-outline-danger btn-sm" type="submit">
          <i class="bi bi-trash me-1"></i> Esvaziar
        </button>
      </form>
    <?php endif ?>
    <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/eventos/' . $evento->id) ?>">
      Voltar ao evento
    </a>
  </div>
</div>

<div class="row g-3">
  <!-- ============ NO CONJUNTO ============ -->
  <div class="col-lg-7">
    <div class="cartao">
      <div class="p-3 border-bottom">
        <h2 class="h6 mb-0">No conjunto</h2>
      </div>

      <?php if (empty($palavras)): ?>
        <?= view('components/estado_vazio', [
            'palavra'  => 'vazio',
            'mensagem' => 'O conjunto está vazio. Adicione palavras ao lado, ou monte-o automaticamente na página do evento.',
        ]) ?>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table tabela-cns align-middle mb-0">
            <thead><tr><th>Palavra</th><th>Dificuldade</th>
                       <th class="text-center">Estado</th><th class="text-end">Ação</th></tr></thead>
            <tbody>
              <?php foreach ($palavras as $p): ?>
                <tr class="<?= $p->usada ? 'opacity-50' : '' ?>">
                  <td class="fw-semibold"><?= esc($p->palavra) ?>
                    <span class="texto-suave small d-block"><?= esc($p->silabacao ?? '') ?></span>
                  </td>
                  <td class="small"><?= esc(lang('Concurso.dificuldade_' . $p->dificuldade)) ?></td>
                  <td class="text-center">
                    <?= $p->usada
                        ? '<span class="badge-estado badge-estado--rascunho">Já saiu</span>'
                        : '<span class="badge-estado badge-estado--validada">Por usar</span>' ?>
                  </td>
                  <td class="text-end">
                    <?php if (! $p->usada): ?>
                      <form method="post"
                            action="<?= site_url('admin/eventos/' . $evento->id . '/pool/' . $p->id . '/remover') ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger" type="submit"
                                aria-label="Remover"><i class="bi bi-x"></i></button>
                      </form>
                    <?php endif ?>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
        <p class="form-text p-3 mb-0">
          Palavras já usadas não podem sair: o histórico das tentativas é intocável.
        </p>
      <?php endif ?>
    </div>
  </div>

  <!-- ============ ADICIONAR À MÃO ============ -->
  <div class="col-lg-5">
    <div class="cartao">
      <div class="p-3 border-bottom">
        <h2 class="h6 mb-2">Adicionar palavras</h2>
        <form method="get" class="d-flex gap-2">
          <input class="form-control form-control-sm" name="q"
                 value="<?= esc($termo, 'attr') ?>" placeholder="Procurar palavra…">
          <button class="btn btn-cns-contorno btn-sm" type="submit"><i class="bi bi-search"></i></button>
        </form>
      </div>

      <form method="post" action="<?= site_url('admin/eventos/' . $evento->id . '/pool/adicionar') ?>">
        <?= csrf_field() ?>

        <div style="max-height:420px;overflow-y:auto">
          <?php if (empty($elegiveis)): ?>
            <div class="p-4 text-center">
              <p class="texto-suave small mb-2">
                Nenhuma palavra elegível.
              </p>
              <p class="small mb-0">
                As palavras têm de estar <strong>validadas</strong> e cobrir as classes
                desta categoria.
              </p>
              <a class="btn btn-cns btn-sm mt-3" href="<?= site_url('admin/palavras?estado=por_validar') ?>">
                Ver palavras por validar
              </a>
            </div>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($elegiveis as $e): ?>
                <li class="list-group-item">
                  <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="palavras[]"
                           value="<?= (int) $e->id ?>" id="pw-<?= (int) $e->id ?>">
                    <label class="form-check-label w-100" for="pw-<?= (int) $e->id ?>">
                      <span class="fw-semibold"><?= esc($e->palavra) ?></span>
                      <span class="badge text-bg-light ms-1">
                        <?= esc(lang('Concurso.dificuldade_' . $e->dificuldade)) ?>
                      </span>
                    </label>
                  </div>
                </li>
              <?php endforeach ?>
            </ul>
          <?php endif ?>
        </div>

        <?php if (! empty($elegiveis)): ?>
          <div class="p-3 border-top">
            <button class="btn btn-cns w-100" type="submit">
              <i class="bi bi-plus-lg me-1"></i> Adicionar selecionadas
            </button>
          </div>
        <?php endif ?>
      </form>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
