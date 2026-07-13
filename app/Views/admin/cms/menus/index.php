<?php /* Gestor de menus: itens por localização + formulário de adição. */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Menus<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<h1 class="h3 mb-1">Menus</h1>
<p class="texto-suave mb-4">
  Os itens aqui definidos aparecem no cabeçalho e no rodapé do portal público.
  Enquanto não houver itens, o portal mostra um menu padrão.
</p>

<div class="row g-3">
  <?php foreach ($menus as $menu): ?>
    <div class="col-lg-6">
      <div class="cartao p-4 h-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 mb-0"><?= esc($menu->nome) ?></h2>
          <span class="badge text-bg-light"><?= esc($menu->localizacao) ?></span>
        </div>

        <!-- Itens existentes -->
        <?php $lista = $itens[$menu->id] ?? []; ?>
        <?php if ($lista === []): ?>
          <p class="texto-suave small">Sem itens. Adicione o primeiro abaixo.</p>
        <?php else: ?>
          <ul class="list-group list-group-flush mb-3">
            <?php foreach ($lista as $item): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span>
                  <span class="texto-suave small me-2">#<?= (int) $item->ordem ?></span>
                  <strong><?= esc($item->label) ?></strong>
                  <span class="badge text-bg-light ms-2"><?= esc($item->tipo) ?></span>
                </span>
                <form method="post" action="<?= site_url('admin/cms/menus/item/' . $item->id . '/eliminar') ?>"
                      onsubmit="return confirm('Remover este item do menu?')">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Remover">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </li>
            <?php endforeach ?>
          </ul>
        <?php endif ?>

        <!-- Adicionar item -->
        <form method="post" action="<?= site_url('admin/cms/menus/item') ?>" class="border-top pt-3">
          <?= csrf_field() ?>
          <input type="hidden" name="menu_id" value="<?= (int) $menu->id ?>">

          <div class="row g-2">
            <div class="col-7">
              <label class="form-label small" for="label-<?= $menu->id ?>">Texto do item</label>
              <input class="form-control form-control-sm" id="label-<?= $menu->id ?>" name="label" required
                     placeholder="Ex.: Regulamento">
            </div>
            <div class="col-5">
              <label class="form-label small" for="ordem-<?= $menu->id ?>">Ordem</label>
              <input class="form-control form-control-sm" id="ordem-<?= $menu->id ?>" name="ordem"
                     type="number" value="<?= count($lista) + 1 ?>">
            </div>

            <div class="col-12">
              <label class="form-label small" for="tipo-<?= $menu->id ?>">Tipo de destino</label>
              <select class="form-select form-select-sm seletor-tipo" id="tipo-<?= $menu->id ?>"
                      name="tipo" data-menu="<?= $menu->id ?>" required>
                <option value="custom">Endereço interno (ex.: /noticias)</option>
                <option value="pagina">Página institucional</option>
                <option value="categoria">Categoria de notícias</option>
                <option value="url_externa">Link externo</option>
              </select>
            </div>

            <!-- Campos condicionais -->
            <div class="col-12 campo-tipo campo-custom" data-menu="<?= $menu->id ?>">
              <label class="form-label small">Endereço</label>
              <input class="form-control form-control-sm" name="url" placeholder="/noticias  ou  https://...">
            </div>
            <div class="col-12 campo-tipo campo-pagina d-none" data-menu="<?= $menu->id ?>">
              <label class="form-label small">Página</label>
              <select class="form-select form-select-sm" name="pagina_id">
                <?php foreach ($paginas as $p): ?>
                  <option value="<?= (int) $p->id ?>"><?= esc($p->titulo) ?></option>
                <?php endforeach ?>
              </select>
            </div>
            <div class="col-12 campo-tipo campo-categoria d-none" data-menu="<?= $menu->id ?>">
              <label class="form-label small">Categoria</label>
              <select class="form-select form-select-sm" name="categoria_id">
                <?php foreach ($categorias as $c): ?>
                  <option value="<?= (int) $c->id ?>"><?= esc($c->nome) ?></option>
                <?php endforeach ?>
              </select>
            </div>

            <div class="col-12 mt-2">
              <button class="btn btn-cns btn-sm w-100" type="submit">
                <i class="bi bi-plus-lg me-1"></i> Adicionar ao menu
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  <?php endforeach ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Mostra apenas o campo relevante para o tipo de destino escolhido.
document.querySelectorAll('.seletor-tipo').forEach(sel => {
  sel.addEventListener('change', () => {
    const menu = sel.dataset.menu;
    document.querySelectorAll(`.campo-tipo[data-menu="${menu}"]`)
      .forEach(c => c.classList.add('d-none'));
    const mapa = { custom: 'campo-custom', url_externa: 'campo-custom',
                   pagina: 'campo-pagina', categoria: 'campo-categoria' };
    document.querySelector(`.${mapa[sel.value]}[data-menu="${menu}"]`)?.classList.remove('d-none');
  });
});
</script>
<?= $this->endSection() ?>
