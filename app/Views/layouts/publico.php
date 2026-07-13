<?php
/**
 * Layout do portal público.
 *
 * O menu vem do MenuService (tabelas menus/menus_itens). Se ainda não
 * houver menus na BD, usa-se um menu PADRÃO — o site nunca fica sem
 * navegação nem rebenta por falta de dados.
 */
$menuHeader = service('menus')->arvore('header');

if ($menuHeader === []) {
  $menuHeader = [
    ['titulo' => 'Início', 'url_final' => site_url(), 'target' => '_self', 'filhos' => []],
    ['titulo' => 'Notícias', 'url_final' => site_url('noticias'), 'target' => '_self', 'filhos' => []],
    ['titulo' => 'Resultados', 'url_final' => site_url('resultados'), 'target' => '_self', 'filhos' => []],
  ];
}

$menuFooter = service('menus')->arvore('footer');
?>
<!doctype html>
<html lang="pt-AO">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($this->renderSection('titulo') ?: 'Concurso Nacional de Soletração') ?></title>
  <?= $this->renderSection('meta') ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800&family=Inter:wght@400;600;700&display=swap"
    rel="stylesheet">

  <!-- Favicons -->
  <link rel="apple-touch-icon" href="<?= base_url('public/assets/img/favicon/apple-touch-icon.png') ?>">
  <link type="image/png" href="<?= base_url('public/assets/img/favicon/favicon-96x96.png') ?>">
  <link type="image/svg+xml" href="<?= base_url('public/assets/img/favicon/favicon.svg') ?>">
  <link rel="manifest" href="<?= base_url('public/assets/img/favicon/site.webmanifest') ?>">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= base_url('public/assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('public/assets/css/tema.css') ?>" rel="stylesheet">
</head>

<body>

  <nav class="navbar navbar-expand-lg navbar-portal sticky-top">
    <div class="container">
      <a class="navbar-brand" href="<?= site_url() ?>">
        <img src="<?= base_url('public/assets/img/logo.png') ?>" alt="Concurso Nacional de Soletração" height="44"
          onerror="this.outerHTML='<span class=\'fw-bold\' style=\'font-family:var(--cns-fonte-display);color:var(--cns-marinho)\'>Soletração</span>'">
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
        aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menu">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="menuPrincipal">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
          <?php foreach ($menuHeader as $item): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= esc($item['url_final'], 'attr') ?>" <?= ($item['target'] ?? '_self') === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>>
                <?= esc($item['titulo']) ?>
              </a>
            </li>
          <?php endforeach ?>

          <li class="nav-item ms-lg-2">
            <a class="btn btn-cns" href="<?= site_url('inscricao') ?>">Inscrever candidato</a>
          </li>

          <?php /* ÁREA DE ACESSO — visível no portal (antes faltava) */ ?>
          <?php if (auth()->loggedIn()): ?>
            <li class="nav-item dropdown ms-lg-1">
              <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                <span
                  class="avatar-inicial"><?= esc(mb_strtoupper(mb_substr(auth()->user()->username ?? 'U', 0, 1))) ?></span>
                <span class="d-lg-none">A minha conta</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item" href="<?= site_url('admin') ?>"><i
                      class="bi bi-speedometer2 me-2"></i>Administração</a></li>
                <li>
                  <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item text-danger" href="<?= site_url('logout') ?>"><i
                      class="bi bi-box-arrow-right me-2"></i>Terminar sessão</a></li>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item ms-lg-1">
              <a class="btn btn-cns-contorno d-inline-flex align-items-center gap-2" href="<?= site_url('login') ?>">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
              </a>
            </li>
          <?php endif ?>
        </ul>
      </div>
    </div>
  </nav>

  <main>
    <?= view('components/flash') ?>
    <?= $this->renderSection('conteudo') ?>
  </main>

  <footer class="rodape-portal">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-5">
          <p class="mb-2 fw-bold text-white" style="font-family:var(--cns-fonte-display)">
            Concurso Nacional de Soletração
          </p>
          <p class="mb-0 small">Valorizar a língua portuguesa e o talento dos alunos angolanos,
            da escola à final nacional.</p>
        </div>
        <div class="col-6 col-lg-3">
          <p class="fw-bold text-white small mb-2">Navegação</p>
          <ul class="list-unstyled small">
            <?php if ($menuFooter === []): ?>
              <li class="mb-1"><a href="<?= site_url('noticias') ?>">Notícias</a></li>
              <li class="mb-1"><a href="<?= site_url('resultados') ?>">Resultados</a></li>
              <li class="mb-1"><a href="<?= site_url('inscricao') ?>">Inscrições</a></li>
            <?php else: ?>
              <?php foreach ($menuFooter as $item): ?>
                <li class="mb-1"><a href="<?= esc($item['url_final'], 'attr') ?>"><?= esc($item['titulo']) ?></a></li>
              <?php endforeach ?>
            <?php endif ?>
          </ul>
        </div>
        <div class="col-6 col-lg-4">
          <p class="fw-bold text-white small mb-2">Newsletter</p>
          <form action="<?= site_url('newsletter/subscrever') ?>" method="post" class="d-flex gap-2">
            <?= csrf_field() ?>
            <label class="visually-hidden" for="nl-email">E-mail</label>
            <input id="nl-email" name="email" type="email" required class="form-control form-control-sm"
              placeholder="O seu e-mail">
            <button class="btn btn-cns btn-sm" type="submit">Subscrever</button>
          </form>
        </div>
      </div>
      <hr class="border-secondary my-4">
      <p class="small mb-0">&copy; <?= date('Y') ?> Concurso Nacional de Soletração | Angola</p>
    </div>
  </footer>

  <script src="<?= base_url('public/assets/js/bootstrap.bundle.min.js') ?>"></script>
  <?= $this->renderSection('scripts') ?>
</body>

</html>