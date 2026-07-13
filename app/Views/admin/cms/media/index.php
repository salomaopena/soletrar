<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Biblioteca de media<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<h1 class="h3 mb-4">Biblioteca de media</h1>

<div class="cartao p-4 mb-4">
  <h2 class="h6 mb-3">Enviar ficheiro</h2>
  <form method="post" action="<?= site_url('admin/cms/media/enviar') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label" for="ficheiro">Ficheiro</label>
        <input class="form-control" type="file" id="ficheiro" name="ficheiro" required>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="titulo">Título</label>
        <input class="form-control" id="titulo" name="titulo">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="texto_alt">Texto alternativo</label>
        <input class="form-control" id="texto_alt" name="texto_alt"
               placeholder="Descrição para acessibilidade">
      </div>
      <div class="col-md-2">
        <button class="btn btn-cns w-100" type="submit">Enviar</button>
      </div>
    </div>
  </form>
</div>

<div class="row g-3">
  <?php foreach ($media as $m): ?>
    <div class="col-6 col-md-3 col-xl-2">
      <div class="cartao p-2 h-100">
        <?php if ($m->tipo === 'imagem'): ?>
          <img src="<?= base_url($m->url) ?>" alt="<?= esc($m->texto_alt ?? '', 'attr') ?>"
               class="w-100 rounded mb-2" style="aspect-ratio:4/3;object-fit:cover">
        <?php else: ?>
          <div class="d-grid place-items-center bg-light rounded mb-2" style="aspect-ratio:4/3">
            <i class="bi bi-file-earmark fs-1 text-muted m-auto"></i>
          </div>
        <?php endif ?>
        <p class="small text-truncate mb-1"><?= esc($m->titulo ?: $m->nome_original) ?></p>
        <form method="post" action="<?= site_url('admin/cms/media/' . $m->id . '/eliminar') ?>"
              onsubmit="return confirm('Eliminar este ficheiro?')">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger w-100" type="submit">Eliminar</button>
        </form>
      </div>
    </div>
  <?php endforeach ?>
  <?php if (empty($media)): ?>
    <div class="col-12"><?= view('components/estado_vazio', ['palavra' => 'vazio', 'mensagem' => 'A biblioteca está vazia.']) ?></div>
  <?php endif ?>
</div>
<?php if (isset($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif ?>
<?= $this->endSection() ?>
