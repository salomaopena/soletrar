<?php /* Listagem editorial de notícias (estilo WordPress: barra de estados + contadores). */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Notícias<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
  <h1 class="h3 mb-0">Notícias</h1>
  <a class="btn btn-cns" href="<?= site_url('admin/cms/noticias/nova') ?>">
    <i class="bi bi-plus-lg me-1"></i> Nova notícia
  </a>
</div>

<ul class="nav nav-pills mb-3 gap-1 flex-wrap">
  <?php $estados = ['todas' => 'Todas', 'rascunho' => 'Rascunhos', 'revisao' => 'Em revisão',
                    'agendada' => 'Agendadas', 'publicada' => 'Publicadas', 'arquivada' => 'Arquivadas']; ?>
  <?php foreach ($estados as $chave => $rotulo): ?>
    <li class="nav-item">
      <a class="nav-link <?= ($estadoAtual ?? 'todas') === $chave ? 'active' : '' ?>"
         href="<?= site_url('admin/cms/noticias?estado=' . $chave) ?>">
        <?= esc($rotulo) ?>
        <?php if ($chave !== 'todas'): ?>
          <span class="badge text-bg-light ms-1"><?= (int) (($contadores ?? [])[$chave] ?? 0) ?></span>
        <?php endif ?>
      </a>
    </li>
  <?php endforeach ?>
</ul>

<div class="cartao">
  <?php if (empty($noticias)): ?>
    <?= view('components/estado_vazio', [
        'palavra'  => 'vazio',
        'mensagem' => 'Ainda não há notícias neste estado.',
        'acao'     => ['url' => site_url('admin/cms/noticias/nova'), 'rotulo' => 'Criar a primeira notícia'],
    ]) ?>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table tabela-cns table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Título</th><th>Estado</th><th>Autor</th>
            <th class="text-center">Visualizações</th><th class="text-end">Atualizada</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($noticias as $n): ?>
            <tr>
              <td>
                <a class="fw-semibold text-decoration-none"
                   href="<?= site_url('admin/cms/noticias/editar/' . $n->id) ?>">
                  <?= esc($n->titulo) ?>
                </a>
                <?php if ($n->fixada): ?><i class="bi bi-pin-angle-fill text-warning ms-1" title="Fixada"></i><?php endif ?>
                <?php if ($n->destaque): ?><i class="bi bi-star-fill text-warning ms-1" title="Destaque"></i><?php endif ?>
              </td>
              <td><?= view('components/badge_estado', ['estado' => $n->status]) ?></td>
              <td class="texto-suave small"><?= esc($n->autor_nome ?? '—') ?></td>
              <td class="text-center"><?= (int) $n->visualizacoes ?></td>
              <td class="text-end texto-suave small"><?= esc(data_exibir($n->updated_at, 'curta')) ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php if (isset($pager)): ?>
      <div class="p-3"><?= $pager->links() ?></div>
    <?php endif ?>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
