<?php /* Estado vazio com as fichas de letras (assinatura visual).
   Uso: view('components/estado_vazio', ['palavra' => 'vazio',
        'mensagem' => 'Ainda não há inscrições…', 'acao' => ['url'=>..., 'rotulo'=>...]]) */ ?>
<div class="estado-vazio">
  <span class="fichas" aria-hidden="true">
    <?php foreach (mb_str_split($palavra ?? 'vazio') as $letra): ?>
      <span class="ficha-letra"><?= esc($letra) ?></span>
    <?php endforeach ?>
  </span>
  <p class="mb-3"><?= esc($mensagem) ?></p>
  <?php if (! empty($acao)): ?>
    <a class="btn btn-cns" href="<?= esc($acao['url'], 'attr') ?>"><?= esc($acao['rotulo']) ?></a>
  <?php endif ?>
</div>
