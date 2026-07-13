<?php
/** Lista de candidatos pronta a imprimir (com coluna de assinatura). */
$aplicados = array_filter($filtros, static fn ($v) => $v !== '' && $v !== null);
?>
<?= $this->extend('layouts/impressao') ?>

<?= $this->section('conteudo') ?>

<?php if ($aplicados !== []): ?>
  <div class="filtros-aplicados">
    <strong>Filtros:</strong>
    <?php foreach ($aplicados as $k => $v): ?>
      <?= esc(str_replace('_id', '', $k)) ?>: <?= esc((string) $v) ?> &nbsp;
    <?php endforeach ?>
    · <strong><?= count($candidatos) ?></strong> candidato(s)
  </div>
<?php endif ?>

<table>
  <thead>
    <tr>
      <th style="width:26px">#</th>
      <th>N.º inscrição</th>
      <th>Candidato</th>
      <th>Cl.</th>
      <th>Escola</th>
      <th>Província</th>
      <th>Encarregado</th>
      <th>Telefone</th>
      <th>Estado</th>
      <th style="width:100px">Assinatura</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($candidatos as $i => $c): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= esc($c->numero_inscricao) ?></td>
        <td><strong><?= esc($c->nome_completo) ?></strong></td>
        <td><?= (int) $c->classe_atual ?>.ª</td>
        <td><?= esc($c->escola) ?></td>
        <td><?= esc($c->provincia) ?></td>
        <td><?= esc($c->encarregado ?? '—') ?></td>
        <td><?= esc($c->telefone ?? '—') ?></td>
        <td><?= esc(lang('Geral.estado_' . $c->status)) ?></td>
        <td><span class="assinatura">&nbsp;</span></td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?php if (empty($candidatos)): ?>
  <p style="margin-top:20px;color:#5A6478">Nenhum candidato corresponde aos filtros.</p>
<?php endif ?>
<?= $this->endSection() ?>
