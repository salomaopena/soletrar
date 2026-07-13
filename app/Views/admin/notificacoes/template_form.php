<?php $erros = session('erros'); ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Editar modelo<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <p class="rotulo-secao mb-1"><?= esc($template->canal) ?></p>
    <h1 class="h3 mb-0"><code><?= esc($template->codigo) ?></code></h1>
  </div>
  <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/notificacoes/templates') ?>">Voltar</a>
</div>

<form method="post" action="<?= site_url('admin/notificacoes/templates/' . $template->id) ?>"
      class="cartao p-4" style="max-width:800px">
  <?= csrf_field() ?>

  <?= view('components/campo', ['nome' => 'nome', 'rotulo' => 'Nome do modelo', 'tipo' => 'text',
      'obrigatorio' => true, 'valor' => old('nome', $template->nome), 'erros' => $erros]) ?>

  <?php if ($template->canal === 'email'): ?>
    <?= view('components/campo', ['nome' => 'assunto', 'rotulo' => 'Assunto', 'tipo' => 'text',
        'valor' => old('assunto', $template->assunto), 'erros' => $erros]) ?>
  <?php endif ?>

  <?= view('components/campo', ['nome' => 'corpo', 'rotulo' => 'Corpo da mensagem',
      'tipo' => 'textarea', 'linhas' => 8, 'obrigatorio' => true,
      'valor' => old('corpo', $template->corpo), 'erros' => $erros]) ?>

  <div class="alert alert-info small">
    <strong>Placeholders</strong> disponíveis (entre chavetas duplas):
    <code>{{candidato_nome}}</code>, <code>{{encarregado_nome}}</code>,
    <code>{{numero_inscricao}}</code>, <code>{{edicao_nome}}</code>,
    <code>{{provincia}}</code>, <code>{{evento_nome}}</code>,
    <code>{{data_evento}}</code>, <code>{{posicao}}</code>,
    <code>{{link_acompanhamento}}</code>, <code>{{link_resultados}}</code>.
    <?php if ($template->canal === 'sms'): ?>
      <div class="mt-2">
        <strong>SMS:</strong> evite acentos (ã, õ, ç). Fora do alfabeto GSM,
        cada mensagem passa de 160 para 70 caracteres — e custa mais.
      </div>
    <?php endif ?>
  </div>

  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
           <?= $template->ativo ? 'checked' : '' ?>>
    <label class="form-check-label" for="ativo">
      Ativo (se desligar, este canal deixa de enviar para este evento)
    </label>
  </div>

  <button class="btn btn-cns" type="submit"><i class="bi bi-save me-1"></i> Guardar</button>
</form>
<?= $this->endSection() ?>
