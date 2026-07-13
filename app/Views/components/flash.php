<?php /* Mensagens flash padronizadas — incluído pelos layouts.
   Uso nos controllers: ->with('sucesso'|'erro'|'aviso', 'mensagem')
   ou ->with('erros', [array de erros de validação]). */ ?>
<?php $mapa = ['sucesso' => 'success', 'erro' => 'danger', 'aviso' => 'warning', 'info' => 'info']; ?>
<div class="container pt-3">
  <?php foreach ($mapa as $chave => $classe): ?>
    <?php if (session()->getFlashdata($chave)): ?>
      <div class="alert alert-<?= $classe ?> alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata($chave)) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
      </div>
    <?php endif ?>
  <?php endforeach ?>

  <?php if ($erros = session()->getFlashdata('erros')): ?>
    <div class="alert alert-danger" role="alert">
      <p class="fw-semibold mb-1">Verifique os campos assinalados:</p>
      <ul class="mb-0">
        <?php foreach ((array) $erros as $erro): ?>
          <li><?= esc($erro) ?></li>
        <?php endforeach ?>
      </ul>
    </div>
  <?php endif ?>
</div>
