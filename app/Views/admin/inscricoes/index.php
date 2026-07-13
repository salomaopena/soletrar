<?php /* Listagem administrativa de inscrições (controller da Fase 4).
   Dados: $inscricoes, $pager, $contadores, $estadoAtual. */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Inscrições<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
  <h1 class="h3 mb-0">Inscrições</h1>
  <?php if (auth()->user()->can('inscricoes.criar')): ?>
    <a class="btn btn-cns" href="<?= site_url('admin/inscricoes/nova') ?>">Nova inscrição</a>
  <?php endif ?>
</div>

<?php /* Barra de estados com contadores (padrão do backoffice, também usado no CMS) */ ?>
<ul class="nav nav-pills mb-3 gap-1">
  <?php foreach (['pendente', 'validada', 'rejeitada'] as $estado): ?>
    <li class="nav-item">
      <a class="nav-link <?= $estadoAtual === $estado ? 'active' : '' ?>"
         href="<?= site_url('admin/inscricoes?status=' . $estado) ?>">
        <?= esc(lang('Geral.estado_' . $estado)) ?>
        <span class="badge text-bg-light ms-1"><?= (int) ($contadores[$estado] ?? 0) ?></span>
      </a>
    </li>
  <?php endforeach ?>
</ul>

<div class="cartao">
  <?php if ($inscricoes === []): ?>
    <?= view('components/estado_vazio', [
        'palavra'  => 'nada',
        'mensagem' => 'Não há inscrições neste estado dentro do seu âmbito.',
    ]) ?>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table tabela-cns table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>N.º</th><th>Candidato</th><th>Classe</th><th>Escola</th>
            <th>Província</th><th>Estado</th><th class="text-end">Recebida</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($inscricoes as $i): ?>
            <tr>
              <td class="texto-suave small"><?= esc($i->numero_inscricao ?? '—') ?></td>
              <td>
                <a class="fw-semibold text-decoration-none"
                   href="<?= rota_segura('admin/inscricoes/ver', $i->id, 'inscricao') ?>">
                  <?= esc($i->nome_completo) ?>
                </a>
              </td>
              <td><?= (int) $i->classe_atual ?>.ª</td>
              <td class="texto-suave"><?= esc($i->escola) ?></td>
              <td class="texto-suave"><?= esc($i->provincia) ?></td>
              <td><?= view('components/badge_estado', ['estado' => $i->status]) ?></td>
              <td class="text-end texto-suave small"><?= esc(data_exibir($i->data_inscricao, 'curta')) ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <div class="p-3"><?= $pager->links() ?></div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
