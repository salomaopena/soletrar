<?php /** Pauta do evento: lista de concorrentes para o secretário do júri. */ ?>
<?= $this->extend('layouts/impressao') ?>

<?= $this->section('conteudo') ?>
<div class="filtros-aplicados">
  <strong><?= esc($evento->nome) ?></strong> ·
  <?= esc(data_exibir($evento->data_evento, 'longa')) ?> ·
  <?= count($participantes) ?> concorrente(s)
</div>

<table>
  <thead>
    <tr>
      <th style="width:36px">N.º</th>
      <th>Candidato</th>
      <th>Cl.</th>
      <th>Escola</th>
      <th>Presença</th>
      <th style="width:110px">Assinatura</th>
      <th style="width:140px">Observações</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($participantes as $p): ?>
      <tr>
        <td><strong><?= esc($p->numero_concorrente) ?></strong></td>
        <td><strong><?= esc($p->nome_completo) ?></strong></td>
        <td><?= (int) $p->classe_atual ?>.ª</td>
        <td><?= esc($p->escola) ?></td>
        <td><?= esc(ucfirst($p->presenca)) ?></td>
        <td><span class="assinatura">&nbsp;</span></td>
        <td>&nbsp;</td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>
<?= $this->endSection() ?>
