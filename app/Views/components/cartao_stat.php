<?php /* Cartão de estatística do dashboard.
   Uso: view('components/cartao_stat',
        ['rotulo' => 'Inscrições validadas', 'valor' => 1240, 'cor' => 'verde-agua']) */ ?>
<div class="cartao stat-card h-100">
  <div class="faixa" style="background:var(--cns-<?= esc($cor ?? 'verde-agua', 'attr') ?>)"></div>
  <div class="valor"><?= esc(number_format((float) $valor, 0, ',', ' ')) ?></div>
  <div class="rotulo"><?= esc($rotulo) ?></div>
  <?php if (! empty($detalhe)): ?>
    <div class="small texto-suave mt-1"><?= esc($detalhe) ?></div>
  <?php endif ?>
</div>
