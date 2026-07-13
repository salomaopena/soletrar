<?php /* Formulário de inscrição pública. Dados: $edicao, $provincias, $categorias. */ ?>
<?= $this->extend('layouts/publico') ?>
<?= $this->section('titulo') ?>Inscrever candidato · <?= esc($edicao->nome) ?><?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="container py-5" style="max-width:760px">
  <p class="rotulo-secao mb-2"><?= esc($edicao->nome) ?></p>
  <h1 class="mb-1">Inscrever candidato</h1>
  <p class="texto-suave mb-4">
    As inscrições estão abertas até <?= esc(data_exibir($edicao->data_encerramento_inscricoes, 'longa')) ?>.
    O candidato concorre na província da escola indicada.
  </p>

  <form method="post" action="<?= site_url('inscricao') ?>" class="cartao p-4 p-md-5">
    <?= csrf_field() ?>

    <h2 class="h5 mb-3">Dados do candidato</h2>
    <div class="row">
      <div class="col-md-8"><?= view('components/campo', ['nome' => 'nome_completo', 'rotulo' => 'Nome completo', 'obrigatorio' => true, 'valor' => old('nome_completo'), 'erros' => session('erros')]) ?></div>
      <div class="col-md-4"><?= view('components/campo', ['nome' => 'nome_preferido', 'rotulo' => 'Nome de palco', 'ajuda' => 'Opcional', 'valor' => old('nome_preferido'), 'erros' => session('erros')]) ?></div>
    </div>
    <div class="row">
      <div class="col-md-4"><?= view('components/campo', ['nome' => 'data_nascimento', 'rotulo' => 'Data de nascimento', 'tipo' => 'date', 'obrigatorio' => true, 'valor' => old('data_nascimento'), 'erros' => session('erros')]) ?></div>
      <div class="col-md-4"><?= view('components/campo', ['nome' => 'genero', 'rotulo' => 'Género', 'tipo' => 'select', 'obrigatorio' => true, 'opcoes' => ['feminino' => 'Feminino', 'masculino' => 'Masculino'], 'valor' => old('genero'), 'erros' => session('erros')]) ?></div>
      <div class="col-md-4"><?= view('components/campo', ['nome' => 'classe_atual', 'rotulo' => 'Classe', 'tipo' => 'select', 'obrigatorio' => true, 'opcoes' => [1 => '1.ª', 2 => '2.ª', 3 => '3.ª', 4 => '4.ª', 5 => '5.ª', 6 => '6.ª', 7 => '7.ª', 8 => '8.ª'], 'valor' => old('classe_atual'), 'erros' => session('erros')]) ?></div>
    </div>

    <h2 class="h5 mb-3 mt-3">Escola e categoria</h2>
    <div class="row">
      <div class="col-md-4"><?= view('components/campo', ['nome' => 'provincia_id', 'rotulo' => 'Província', 'tipo' => 'select', 'obrigatorio' => true, 'opcoes' => array_column($provincias, 'nome', 'id'), 'valor' => old('provincia_id'), 'erros' => session('erros')]) ?></div>
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
    <?= view('components/campo', ['nome' => 'categoria_id', 'rotulo' => 'Categoria', 'tipo' => 'select', 'obrigatorio' => true, 'opcoes' => array_column($categorias, 'nome', 'id'), 'valor' => old('categoria_id'), 'ajuda' => 'A categoria deve corresponder à classe/idade do candidato.', 'erros' => session('erros')]) ?>

    <h2 class="h5 mb-3 mt-3">Encarregado de educação</h2>
    <div class="row">
      <div class="col-md-8"><?= view('components/campo', ['nome' => 'enc_nome_completo', 'rotulo' => 'Nome do encarregado', 'obrigatorio' => true, 'valor' => old('enc_nome_completo'), 'erros' => session('erros')]) ?></div>
      <div class="col-md-4"><?= view('components/campo', ['nome' => 'enc_parentesco', 'rotulo' => 'Parentesco', 'tipo' => 'select', 'obrigatorio' => true, 'opcoes' => ['mae' => 'Mãe', 'pai' => 'Pai', 'tutor' => 'Tutor(a)', 'outro' => 'Outro'], 'valor' => old('enc_parentesco'), 'erros' => session('erros')]) ?></div>
    </div>
    <div class="row">
      <div class="col-md-6"><?= view('components/campo', ['nome' => 'enc_telefone', 'rotulo' => 'Telefone', 'ajuda' => 'Formato: 9XXXXXXXX (receberá SMS)', 'obrigatorio' => true, 'valor' => old('enc_telefone'), 'erros' => session('erros')]) ?></div>
      <div class="col-md-6"><?= view('components/campo', ['nome' => 'enc_email', 'rotulo' => 'E-mail', 'tipo' => 'email', 'ajuda' => 'Opcional, mas recomendado', 'valor' => old('enc_email'), 'erros' => session('erros')]) ?></div>
    </div>

    <div class="form-check my-4">
      <input class="form-check-input" type="checkbox" id="enc_autorizou" name="enc_autorizou" value="1" required>
      <label class="form-check-label" for="enc_autorizou">
        Autorizo a participação do candidato no concurso e o tratamento dos seus dados para este efeito.
      </label>
    </div>

    <button class="btn btn-cns btn-lg w-100" type="submit">Submeter inscrição</button>
  </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Dropdowns dependentes província → município → escola.
// (endpoints AJAX servidos pelo InscricaoController; sem framework JS)
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
const selProv = document.getElementById('campo-provincia_id');
const selMun  = document.getElementById('campo-municipio_id');
const selEsc  = document.getElementById('campo-escola_id');

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
