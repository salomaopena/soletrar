<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Comentários<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<h1 class="h3 mb-4">Comentários</h1>

<ul class="nav nav-pills mb-3 gap-1">
  <?php foreach (['pendente' => 'Pendentes', 'aprovado' => 'Aprovados', 'spam' => 'Spam'] as $k => $r): ?>
    <li class="nav-item">
      <a class="nav-link <?= $estadoAtual === $k ? 'active' : '' ?>"
         href="<?= site_url('admin/cms/comentarios?estado=' . $k) ?>"><?= esc($r) ?></a>
    </li>
  <?php endforeach ?>
</ul>

<div class="cartao">
  <?php if (empty($comentarios)): ?>
    <?= view('components/estado_vazio', ['palavra' => 'zero', 'mensagem' => 'Nada para moderar aqui.']) ?>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table tabela-cns align-middle mb-0">
        <thead><tr><th>Autor</th><th>Comentário</th><th>Notícia</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
          <?php foreach ($comentarios as $c): ?>
            <tr>
              <td class="fw-semibold"><?= esc($c->nome_autor ?? 'Utilizador') ?></td>
              <td class="small"><?= esc($c->conteudo) ?></td>
              <td class="texto-suave small"><?= esc($c->noticia_titulo) ?></td>
              <td class="text-end">
                <?php foreach (['aprovado' => ['Aprovar', 'success'], 'spam' => ['Spam', 'warning'], 'lixeira' => ['Apagar', 'danger']] as $acao => [$rot, $cor]): ?>
                  <?php if ($estadoAtual !== $acao): ?>
                    <form method="post" class="d-inline"
                          action="<?= site_url('admin/cms/comentarios/' . $c->id . '/' . $acao) ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-outline-<?= $cor ?>" type="submit"><?= $rot ?></button>
                    </form>
                  <?php endif ?>
                <?php endforeach ?>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <div class="p-3"><?= $pager->links() ?></div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
