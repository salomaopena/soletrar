<?php /** Ficha completa do candidato: dados, encarregados e histórico de inscrições. */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= esc($candidato->nome_completo) ?><?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <p class="rotulo-secao mb-1">Ficha do candidato</p>
    <h1 class="h3 mb-0"><?= esc($candidato->nome_completo) ?></h1>
  </div>
  <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/candidatos') ?>">Voltar à pesquisa</a>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="cartao p-4 mb-3">
      <h2 class="h6 mb-3">Dados pessoais</h2>
      <div class="row g-3">
        <div class="col-md-4"><div class="rotulo-secao">Data de nascimento</div>
          <div><?= esc(data_exibir($candidato->data_nascimento, 'curta')) ?>
            <span class="texto-suave">(<?= idade($candidato->data_nascimento) ?> anos)</span></div></div>
        <div class="col-md-4"><div class="rotulo-secao">Género</div>
          <div><?= esc(ucfirst($candidato->genero)) ?></div></div>
        <div class="col-md-4"><div class="rotulo-secao">Classe</div>
          <div><?= (int) $candidato->classe_atual ?>.ª</div></div>
        <div class="col-md-6"><div class="rotulo-secao">Escola</div>
          <div><?= esc($candidato->escola) ?></div></div>
        <div class="col-md-3"><div class="rotulo-secao">Município</div>
          <div><?= esc($candidato->municipio ?? '—') ?></div></div>
        <div class="col-md-3"><div class="rotulo-secao">Província</div>
          <div><?= esc($candidato->provincia) ?></div></div>
        <div class="col-md-6"><div class="rotulo-secao">BI / Cédula</div>
          <div><?= esc($candidato->bi_numero ?: ($candidato->cedula_numero ?? '—')) ?></div></div>
      </div>
    </div>

    <div class="cartao p-4">
      <h2 class="h6 mb-3">Histórico de inscrições</h2>
      <?php if (empty($inscricoes)): ?>
        <p class="texto-suave small mb-0">Sem inscrições registadas.</p>
      <?php else: ?>
        <table class="table tabela-cns align-middle mb-0">
          <thead><tr><th>N.º</th><th>Edição</th><th>Categoria</th><th>Estado</th></tr></thead>
          <tbody>
            <?php foreach ($inscricoes as $i): ?>
              <tr>
                <?php /* numero_inscricao pertence à tabela `candidatos` (é o
                        MESMO valor para todas as inscrições deste candidato) —
                        `inscricoes` não tem essa coluna. */ ?>
                <td class="texto-suave small"><?= esc($candidato->numero_inscricao) ?></td>
                <td><?= esc($i->edicao) ?></td>
                <td class="texto-suave"><?= esc($i->categoria ?? '—') ?></td>
                <td><?= view('components/badge_estado', ['estado' => $i->status]) ?></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      <?php endif ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="cartao p-4">
      <h2 class="h6 mb-3">Encarregados de educação</h2>
      <?php foreach ($encarregados as $e): ?>
        <div class="border-bottom py-2">
          <div class="fw-semibold">
            <?= esc($e->nome_completo) ?>
            <?php if ($e->principal): ?>
              <span class="badge text-bg-light ms-1">Principal</span>
            <?php endif ?>
          </div>
          <div class="small texto-suave">
            <?= esc(ucfirst($e->parentesco)) ?> ·
            <?= esc(telefone_formatar($e->telefone)) ?>
            <?php if ($e->email): ?> · <?= esc($e->email) ?><?php endif ?>
          </div>
        </div>
      <?php endforeach ?>
      <?php if (empty($encarregados)): ?>
        <p class="texto-suave small mb-0">Sem encarregado registado.</p>
      <?php endif ?>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
