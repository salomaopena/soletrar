<?php
/** Atribuições territoriais de um utilizador (coordenadores_atribuicao). */
$temAtiva = false;
foreach ($atribuicoes as $a) { if ($a->ativo) { $temAtiva = true; } }
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Atribuições<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <p class="rotulo-secao mb-1">Âmbito territorial</p>
    <h1 class="h3 mb-0"><?= esc($utilizador->nome_completo ?: $utilizador->username) ?></h1>
    <span class="texto-suave small">
      <?php foreach ($grupos as $g): ?>
        <span class="badge text-bg-light"><?= esc($g) ?></span>
      <?php endforeach ?>
    </span>
  </div>
  <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/sistema/utilizadores') ?>">Voltar</a>
</div>

<?php if (! $temAtiva): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>Este utilizador <strong>não tem atribuição ativa</strong> — não consegue ver
          quaisquer dados. Crie uma abaixo.</span>
  </div>
<?php endif ?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="cartao">
      <div class="p-3 border-bottom"><h2 class="h6 mb-0">Atribuições</h2></div>

      <?php if (empty($atribuicoes)): ?>
        <?= view('components/estado_vazio', [
            'palavra'  => 'zero',
            'mensagem' => 'Sem atribuições. Sem território, o utilizador não vê nada.',
        ]) ?>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table tabela-cns align-middle mb-0">
            <thead><tr><th>Nível</th><th>Território</th><th>Período</th>
                       <th class="text-center">Ativa</th><th class="text-end">Ação</th></tr></thead>
            <tbody>
              <?php foreach ($atribuicoes as $a): ?>
                <tr class="<?= $a->ativo ? '' : 'opacity-50' ?>">
                  <td><span class="badge text-bg-light"><?= esc($a->nivel) ?></span></td>
                  <td class="fw-semibold">
                    <?= esc($a->escola ?? $a->municipio ?? $a->provincia ?? 'Todo o país') ?>
                  </td>
                  <td class="small texto-suave">
                    <?= esc(data_exibir($a->data_inicio, 'curta')) ?>
                    <?= $a->data_fim ? ' — ' . esc(data_exibir($a->data_fim, 'curta')) : '' ?>
                  </td>
                  <td class="text-center">
                    <?= $a->ativo
                        ? '<i class="bi bi-check-circle-fill text-success"></i>'
                        : '<i class="bi bi-dash-circle text-muted"></i>' ?>
                  </td>
                  <td class="text-end">
                    <form method="post"
                          action="<?= site_url('admin/sistema/utilizadores/' . $utilizador->id
                                             . '/atribuicoes/' . $a->id . '/alternar') ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-cns-contorno" type="submit">
                        <?= $a->ativo ? 'Desativar' : 'Ativar' ?>
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
  </div>

  <div class="col-lg-5">
    <form method="post"
          action="<?= site_url('admin/sistema/utilizadores/' . $utilizador->id . '/atribuicoes') ?>"
          class="cartao p-4">
      <?= csrf_field() ?>
      <h2 class="h6 mb-3">Nova atribuição</h2>
      <?php $erros = session('erros'); ?>

      <?= view('components/campo', ['nome' => 'nivel', 'rotulo' => 'Nível', 'tipo' => 'select',
          'obrigatorio' => true, 'erros' => $erros, 'valor' => old('nivel'),
          'opcoes' => [
              'nacional'   => 'Nacional (vê tudo)',
              'provincial' => 'Provincial',
              'municipal'  => 'Municipal',
              'escolar'    => 'Escolar',
          ]]) ?>

      <div id="bloco-provincia">
        <?= view('components/campo', ['nome' => 'provincia_id', 'rotulo' => 'Província',
            'tipo' => 'select', 'opcoes' => $provincias, 'valor' => old('provincia_id'),
            'erros' => $erros]) ?>
      </div>
      <div id="bloco-municipio">
        <?= view('components/campo', ['nome' => 'municipio_id', 'rotulo' => 'Município',
            'tipo' => 'select', 'opcoes' => $municipios, 'valor' => old('municipio_id'),
            'erros' => $erros]) ?>
      </div>
      <div id="bloco-escola">
        <?= view('components/campo', ['nome' => 'escola_id', 'rotulo' => 'Escola',
            'tipo' => 'select', 'opcoes' => $escolas, 'valor' => old('escola_id'),
            'erros' => $erros]) ?>
      </div>

      <?= view('components/campo', ['nome' => 'data_inicio', 'rotulo' => 'Início',
          'tipo' => 'date', 'valor' => old('data_inicio', date('Y-m-d')), 'erros' => $erros]) ?>

      <button class="btn btn-cns w-100" type="submit">
        <i class="bi bi-plus-lg me-1"></i> Atribuir território
      </button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Mostra apenas o campo de território correspondente ao nível escolhido.
const nivel = document.getElementById('campo-nivel');
const blocos = {
  provincial: 'bloco-provincia',
  municipal:  'bloco-municipio',
  escolar:    'bloco-escola',
};

function alternar() {
  Object.values(blocos).forEach(id => {
    document.getElementById(id).style.display = 'none';
  });
  if (blocos[nivel.value]) {
    document.getElementById(blocos[nivel.value]).style.display = '';
  }
}
nivel.addEventListener('change', alternar);
alternar();
</script>
<?= $this->endSection() ?>
