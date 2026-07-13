<?php /* Badge de estado do domínio.
   Uso: <?= view('components/badge_estado', ['estado' => $inscricao->status]) ?>
   O rótulo vem de Language/pt-AO/Geral.php: 'estado_pendente' => 'Pendente', ... */ ?>
<span class="badge-estado badge-estado--<?= esc(str_replace('_', '-', $estado), 'attr') ?>">
  <?= esc(lang('Geral.estado_' . $estado)) ?>
</span>
