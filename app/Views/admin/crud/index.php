<?php
/**
 * Listagem CRUD genérica.
 *
 * Parâmetros:
 *   $titulo      string
 *   $rotaBase    string  ex.: 'admin/geografia/escolas'
 *   $colunas     array   ['campo' => 'Rótulo'] ou ['campo' => ['rotulo'=>..,'tipo'=>'badge|data|bool']]
 *   $registos    array de objetos
 *   $pager       (opcional)
 *   $podeCriar   bool
 *   $podeEditar  bool    (default true)
 *   $campoId     string  coluna usada nos links (default 'id';
 *                        ex.: 'chave' na tabela `configuracoes`, que não tem id)
 *   $vazio       string  mensagem de estado vazio
 */
$campoId = $campoId ?? 'id';
$podeEditar = $podeEditar ?? true;
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= esc($titulo) ?><?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
  <h1 class="h3 mb-0"><?= esc($titulo) ?></h1>
  <?php if ($podeCriar ?? true): ?>
    <a class="btn btn-cns" href="<?= site_url($rotaBase . '/nova') ?>">
      <i class="bi bi-plus-lg me-1"></i> Adicionar
    </a>
  <?php endif ?>
</div>

<div class="cartao">
  <?php if (empty($registos)): ?>
    <?= view('components/estado_vazio', [
      'palavra' => 'vazio',
      'mensagem' => $vazio ?? 'Ainda não há registos.',
      'acao' => ($podeCriar ?? true)
        ? ['url' => site_url($rotaBase . '/nova'), 'rotulo' => 'Adicionar o primeiro']
        : null,
    ]) ?>
  <?php else: ?>
    <div class="card">
      <div class="card-body p-2">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <?php foreach ($colunas as $def): ?>
                  <th><?= esc(is_array($def) ? $def['rotulo'] : $def) ?></th>
                <?php endforeach ?>
                <?php if ($podeEditar): ?>
                  <th class="text-end">Ações</th><?php endif ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($registos as $r): ?>
                <tr>
                  <?php foreach ($colunas as $campo => $def): ?>
                    <?php
                    $tipo = is_array($def) ? ($def['tipo'] ?? 'texto') : 'texto';
                    $valor = $r->{$campo} ?? null;
                    ?>
                    <td>
                      <?php if ($tipo === 'badge' && $valor !== null): ?>
                        <?= view('components/badge_estado', ['estado' => $valor]) ?>
                      <?php elseif ($tipo === 'data'): ?>
                        <span class="texto-suave small"><?= esc(data_exibir($valor, 'curta')) ?></span>
                      <?php elseif ($tipo === 'bool'): ?>
                        <?= $valor
                          ? '<i class="bi bi-check-circle-fill text-success"></i>'
                          : '<i class="bi bi-dash-circle text-muted"></i>' ?>
                      <?php else: ?>
                        <?= esc((string) ($valor ?? '—')) ?>
                      <?php endif ?>
                    </td>
                  <?php endforeach ?>
                  <?php if ($podeEditar): ?>
                    <td class="text-end">
                      <a class="btn btn-sm btn-cns-contorno"
                        href="<?= site_url($rotaBase . '/editar/' . rawurlencode((string) $r->{$campoId})) ?>">
                        <i class="bi bi-pencil"></i> Editar
                      </a>
                    </td>
                  <?php endif ?>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php if (isset($pager) && $pager !== null): ?>
      <div class="p-3"><?= $pager->links() ?></div>
    <?php endif ?>
  <?php endif ?>
</div>
<?= $this->endSection() ?>