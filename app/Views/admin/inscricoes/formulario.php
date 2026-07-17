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
        <?= view('components/campo', [
          'nome' => 'nome_completo',
          'rotulo' => 'Nome completo',
          'obrigatorio' => true,
          'valor' => old('nome_completo'),
          'erros' => session('erros')
        ]) ?>
      </div>
      <div class="col-md-4">
        <?= view('components/campo', [
          'nome' => 'nome_preferido',
          'rotulo' => 'Nome de palco',
          'ajuda' => 'Opcional',
          'valor' => old('nome_preferido'),
          'erros' => session('erros')
        ]) ?>
      </div>

      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'data_nascimento',
          'rotulo' => 'Data de nascimento',
          'tipo' => 'date',
          'obrigatorio' => true,
          'valor' => old('data_nascimento'),
          'erros' => session('erros')
        ]) ?>
      </div>

      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'genero',
          'rotulo' => 'Género',
          'tipo' => 'select',
          'obrigatorio' => true,
          'opcoes' => ['M' => 'Masculino', 'F' => 'Feminino'],
          'valor' => old('genero'),
          'erros' => session('erros')
        ]) ?>
      </div>

      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'bi_numero',
          'rotulo' => 'N.º de BI/cedula',
          'obrigatorio' => true,
          'ajuda' => 'Opcional, mas recomendado',
          'valor' => old('bi_numero'),
          'erros' => session('erros')
        ]) ?>
      </div>
      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'turma',
          'rotulo' => 'Turma',
          'ajuda' => 'Opcional (ex.: A, B, C)',
          'valor' => old('turma'),
          'erros' => session('erros')
        ]) ?>
      </div>

      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'telefone_contacto',
          'rotulo' => 'Telefone do candidato',
          'obrigatorio' => true,
          'ajuda' => 'Formato: 9XXXXXXXX (receberá SMS)',
          'valor' => old('telefone_contacto'),
          'erros' => session('erros')
        ]) ?>
      </div>

      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'email_contacto',
          'rotulo' => 'E-mail do candidato',
          'tipo' => 'email',
          'valor' => old('email_contacto'),
          'erros' => session('erros')
        ]) ?>
      </div>

      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'tem_necessidades_especiais',
          'rotulo' => 'Necessidades especiais?',
          'tipo' => 'select',
          'obrigatorio' => true,
          'opcoes' => [0 => 'Não', 1 => 'Sim'],
          'valor' => old('tem_necessidades_especiais'),
          'erros' => session('erros')
        ]) ?>
      </div>
      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'descricao_necessidades',
          'rotulo' => 'Descrição das necessidades',
          'valor' => old('descricao_necessidades'),
          'erros' => session('erros')
        ]) ?>
      </div>

      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'idioma_materno',
          'rotulo' => 'Idioma materno',
          'valor' => old('idioma_materno'),
          'erros' => session('erros')
        ]) ?>
      </div>

      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'outros_idiomas',
          'rotulo' => 'Outros idiomas',
          'valor' => old('outros_idiomas'),
          'erros' => session('erros')
        ]) ?>
      </div>

      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'endereco',
          'rotulo' => 'Endereço',
          'valor' => old('endereco'),
          'erros' => session('erros')
        ]) ?>
      </div>
      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'notas',
          'rotulo' => 'Notas adicionais',
          'valor' => old('notas'),
          'erros' => session('erros')
        ]) ?>
      </div>

    </div>

    <h2 class="h6 mb-3 mt-2">Escola e categoria</h2>

    <div class="row">
      <div class="col-md-4">
        <?= view('components/campo', [
          'nome' => 'provincia_id',
          'rotulo' => 'Província',
          'tipo' => 'select',
          'obrigatorio' => true,
          'opcoes' => array_column($provincias, 'nome', 'id'),
          'valor' => old('provincia_id'),
          'erros' => session('erros')
        ]) ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="campo-municipio_id">Município <span class="text-danger">*</span></label>
        <select class="form-select" id="campo-municipio_id" name="municipio_id" required disabled>
          <option value="">Escolha a província primeiro</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="campo-escola_id">Escola <span class="text-danger">*</span></label>
        <select class="form-select" id="campo-escola_id" name="escola_id" required disabled>
          <option value="">Escolha o município primeiro</option>
        </select>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">

        <?= view('components/campo', [
          'nome' => 'categoria_id',
          'rotulo' => 'Categoria',
          'tipo' => 'select',
          'obrigatorio' => true,
          'opcoes' => array_column($categorias, 'nome', 'id'),
          'valor' => old('categoria_id'),
          //'ajuda' => 'A categoria deve corresponder à classe/idade do candidato.',
          'erros' => session('erros')
        ]) ?>
      </div>
      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'classe_atual',
          'rotulo' => 'Classe',
          'tipo' => 'select',
          'obrigatorio' => true,
          'opcoes' => [6 => '6.ª', 7 => '7.ª', 8 => '8.ª'],
          'valor' => old('classe_atual'),
          'erros' => session('erros')
        ]) ?>
      </div>
    </div>

    <h2 class="h6 mb-3 mt-2">Encarregado de educação</h2>
    <div class="row">
      <div class="col-md-8">
        <?= view('components/campo', [
          'nome' => 'enc_nome_completo',
          'rotulo' => 'Nome do encarregado',
          'obrigatorio' => true,
          'valor' => old('enc_nome_completo'),
          'erros' => session('erros')
        ]) ?>
      </div>
      <div class="col-md-4">
        <?= view('components/campo', [
          'nome' => 'enc_parentesco',
          'rotulo' => 'Parentesco',
          'tipo' => 'select',
          'obrigatorio' => true,
          'opcoes' => ['mae' => 'Mãe', 'pai' => 'Pai', 'tutor' => 'Tutor(a)', 'outro' => 'Outro'],
          'valor' => old('enc_parentesco'),
          'erros' => session('erros')
        ]) ?>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'enc_bi_numero',
          'rotulo' => 'N.º de BI/cedula do encarregado',
          'valor' => old('enc_bi_numero'),
          'erros' => session('erros')
        ]) ?>
      </div>
      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'enc_profissao',
          'rotulo' => 'Profissão do encarregado',
          'valor' => old('enc_profissao'),
          'erros' => session('erros')
        ]) ?>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'enc_telefone',
          'rotulo' => 'Telefone',
          'ajuda' => 'Formato: 9XXXXXXXX (receberá SMS)',
          'obrigatorio' => true,
          'valor' => old('enc_telefone'),
          'erros' => session('erros')
        ]) ?>
      </div>
      <div class="col-md-6">
        <?= view('components/campo', [
          'nome' => 'enc_email',
          'rotulo' => 'E-mail',
          'tipo' => 'email',
          'ajuda' => 'Opcional, mas recomendado',
          'valor' => old('enc_email'),
          'erros' => session('erros')
        ]) ?>
      </div>
    </div>

    <input type="hidden" name="enc_autorizou" value="1">
    <p class="form-text">Ao submeter, confirma que possui a autorização assinada do encarregado.</p>

    <button class="btn btn-cns" type="submit">Registar inscrição</button>
  </form>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  // Dropdowns dependentes província → município → escola.
  // (endpoints AJAX servidos pelo InscricaoController; sem framework JS)
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const selProv = document.getElementById('campo-provincia_id');
  const selMun = document.getElementById('campo-municipio_id');
  const selEsc = document.getElementById('campo-escola_id');

  async function carregar(url, destino, textoVazio) {
    destino.innerHTML = `<option value="">A carregar…</option>`;
    destino.disabled = true;
    const dados = await (await fetch(url)).json();
    destino.innerHTML = `<option value="">${textoVazio}</option>` +
      dados.map(d => `<option value="${d.id}">${d.nome}</option>`).join('');
    destino.disabled = false;
  }

  selProv?.addEventListener('change', e => {
    if (!e.target.value) return;
    carregar(`<?= site_url('inscricao/municipios') ?>/${e.target.value}`, selMun, 'Selecionar município');
    selEsc.innerHTML = `<option value="">Escolha o município primeiro</option>`;
    selEsc.disabled = true;
  });
  selMun?.addEventListener('change', e => {
    if (!e.target.value) return;
    carregar(`<?= site_url('inscricao/escolas') ?>/${e.target.value}`, selEsc, 'Selecionar escola');
  });
</script>
<?= $this->endSection() ?>