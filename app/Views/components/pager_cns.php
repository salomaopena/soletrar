<?php /* Template de paginação (registar em Config/Pager.php:
   public array $templates = ['default_full' => 'components/pager_cns', ...]); */ ?>
<?php $pager->setSurroundCount(2) ?>
<nav aria-label="Paginação">
  <ul class="pagination justify-content-center">
    <?php if ($pager->hasPrevious()): ?>
      <li class="page-item"><a class="page-link" href="<?= $pager->getPrevious() ?>" rel="prev">Anterior</a></li>
    <?php endif ?>
    <?php foreach ($pager->links() as $link): ?>
      <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
        <a class="page-link" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a>
      </li>
    <?php endforeach ?>
    <?php if ($pager->hasNext()): ?>
      <li class="page-item"><a class="page-link" href="<?= $pager->getNext() ?>" rel="next">Seguinte</a></li>
    <?php endif ?>
  </ul>
</nav>
