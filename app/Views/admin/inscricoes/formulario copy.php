<?php /* Inscrição assistida pelo coordenador/professor.
Usa o MESMO InscricaoService do formulário público (Fase 6). */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Nova inscrição<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">Nova inscrição</h1>
  <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/inscricoes') ?>">Voltar</a>
</div>

<?php if ($edicao === null): ?>
  <div class="cartao p-5">
    <?= view('components/estado_vazio', [
      'palavra' => 'fechado',
      'mensagem' => 'Não há nenhuma edição com inscrições abertas. Crie/abra uma edição primeiro.',
      'acao' => auth()->user()->can('concurso.edicoes.gerir')
        ? ['url' => site_url('admin/edicoes'), 'rotulo' => 'Gerir edições'] : null,
    ]) ?>
  </div>
<?php else: ?>
  <form method="post" action="<?= site_url('admin/inscricoes') ?>" class="cartao p-4" style="max-width:900px">
    <?= csrf_field() ?>
    <?php $erros = session('erros'); ?>

    <h2 class="h6 mb-3">Candidato</h2>
    <div class="row">
      <div class="col-md-8">
        <?= view('components/campo', ['nome' => 'nome_completo', 'rotulo' => 'Nome completo', 'obrigatorio' => true, 'valor' => old('nome_completo'), 'erros' => $erros]) ?>
      </div>
      <div class="col-md-4">
        <?= view('components/campo', ['nome' => 'data_nascimento', 'rotulo' => 'Data de nascimento', 'tipo' => 'date', 'obrigatorio' => true, 'valor' => old('data_nascimento'), 'erros' => $erros]) ?>
      </div>
      <div class="col-md-4">
        <?= view('components/campo', ['nome' => 'genero', 'rotulo' => 'Género', 'tipo' => 'select', 'obrigatorio' => true, 'opcoes' => ['M' => 'Masculino', 'F' => 'Feminino'], 'valor' => old('genero'), 'erros' => $erros]) ?>
      </div>
      <div class="col-md-4">
        <?= view('components/campo', ['nome' => 'classe_atual', 'rotulo' => 'Classe', 'tipo' => 'select', 'obrigatorio' => true, 'opcoes' => [6 => '6.ª', 7 => '7.ª', 8 => '8.ª'], 'valor' => old('classe_atual'), 'erros' => $erros]) ?>
      </div>
      <div class="col-md-4">
        <?= view('components/campo', ['nome' => 'turma', 'rotulo' => 'Turma', 'valor' => old('turma'), 'erros' => $erros]) ?>
      </div>
    </div>

    <h2 class="h6 mb-3 mt-2">Escola e categoria</h2>
    <?php
    // A província deriva da escola (RN-01 por construção — Fase 6).
    $opcoesEscolas = [];
    foreach (model('EscolaModel')->where('ativo', 1)->orderBy('nome')->findAll() as $e) {
      $opcoesEscolas[$e->id] = $e->nome;
    }
    $opcoesCategorias = [];
    foreach ($categorias as $c) {
      $opcoesCategorias[$c->id] = $c->nome;
    }
    ?>
    <div class="row">
      <div class="col-md-8">
        <?= view('components/campo', ['nome' => 'escola_id', 'rotulo' => 'Escola', 'tipo' => 'select', 'obrigatorio' => true, 'opcoes' => $opcoesEscolas, 'ajuda' => 'A província do candidato é a da escola.', 'valor' => old('escola_id'), 'erros' => $erros]) ?>
      </div>
      <div class="col-md-4">
        <?= view('components/campo', ['nome' => 'categoria_id', 'rotulo' => 'Categoria', 'tipo' => 'select', 'obrigatorio' => true, 'opcoes' => $opcoesCategorias, 'valor' => old('categoria_id'), 'erros' => $erros]) ?>
      </div>
    </div>

    <h2 class="h6 mb-3 mt-2">Encarregado de educação</h2>
    <div class="row">
      <div class="col-md-6">
        <?= view('components/campo', ['nome' => 'enc_nome_completo', 'rotulo' => 'Nome do encarregado', 'obrigatorio' => true, 'valor' => old('enc_nome_completo'), 'erros' => $erros]) ?>
      </div> 
      <div class="col-md-3">
        <?= view('components/campo', ['nome' => 'enc_parentesco', 'rotulo' => 'Parentesco', 'tipo' => 'select', 'obrigatorio' => true, 'opcoes' => ['mae' => 'Mãe', 'pai' => 'Pai', 'tutor' => 'Tutor(a)', 'outro' => 'Outro'], 'valor' => old('enc_parentesco'), 'erros' => $erros]) ?>
      </div>
      <div class="col-md-3">
        <?= view('components/campo', ['nome' => 'enc_telefone', 'rotulo' => 'Telefone', 'obrigatorio' => true, 'ajuda' => '9XXXXXXXX', 'valor' => old('enc_telefone'), 'erros' => $erros]) ?>
      </div>
      <div class="col-md-6">
        <?= view('components/campo', ['nome' => 'enc_email', 'rotulo' => 'E-mail', 'tipo' => 'email', 'valor' => old('enc_email'), 'erros' => $erros]) ?>
      </div>
    </div>

    <input type="hidden" name="enc_autorizou" value="1">
    <p class="form-text">Ao submeter, confirma que possui a autorização assinada do encarregado.</p>

    <button class="btn btn-cns" type="submit">Registar inscrição</button>
  </form>
<?php endif ?>
<?= $this->endSection() ?>