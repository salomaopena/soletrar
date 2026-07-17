<?php /** Lista de premiados para a cerimónia de entrega. */ ?>
<?= $this->extend('layouts/impressao') ?>
<?= $this->section('conteudo') ?>
<div class="filtros-aplicados">
  <strong><?= esc($evento->nome) ?></strong> — Lista de premiados
</div>
<table>
  <thead>
    <tr><th style="width:36px">Pos.</th><th>Prémio</th><th>Vencedor</th><th>Escola</th>
        <th style="width:110px">Assinatura</th></tr>
  </thead>
  <tbody>
    <?php foreach ($candidatos as $c): ?>
      <tr>
        <td><strong><?= (int) $c->posicao ?>.º</strong></td>
        <td><?= esc($c->premio_nome) ?></td>
        <td><strong><?= esc($c->nome_completo) ?></strong></td>
        <td><?= esc($c->escola ?? '—') ?></td>
        <td><span class="assinatura">&nbsp;</span></td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>
<?= $this->endSection() ?>
