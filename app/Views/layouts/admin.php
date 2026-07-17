<?php
/**
 * Layout administrativo — estilo AdminLTE sobre Bootstrap 5.3.
 *
 * Estrutura: navbar superior fixa + sidebar (offcanvas em <992px, fixa em
 * desktop) + área de conteúdo. O menu esconde secções conforme as
 * permissões Shield do utilizador.
 */
$rota = uri_string();

/** Marca 'active' quando a rota atual começa pelo prefixo dado. */
$ativo = static fn(string $prefixo): string => str_starts_with($rota, $prefixo) ? 'active' : '';
?>
<!doctype html>
<html lang="pt-AO">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($this->renderSection('titulo') ?: 'Administração') ?> · Soletração</title>
  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800&family=Inter:wght@400;600;700&display=swap"
    rel="stylesheet">

  <link rel="apple-touch-icon" href="<?= base_url('public/assets/img/favicon/apple-touch-icon.png') ?>">
  <link type="image/png" href="<?= base_url('public/assets/img/favicon/favicon-96x96.png') ?>">
  <link type="image/svg+xml" href="<?= base_url('public/assets/img/favicon/favicon.svg') ?>">
  <link rel="manifest" href="<?= base_url('public/assets/img/favicon/site.webmanifest') ?>">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= base_url('public/assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('public/assets/css/tema.css') ?>" rel="stylesheet">

</head>

<body class="admin-body">

  <!-- ===================== NAVBAR SUPERIOR ===================== -->
  <nav class="navbar navbar-expand navbar-admin fixed-top">
    <div class="container-fluid px-3">
      <!-- Botão do menu: offcanvas em mobile, colapso em desktop -->
      <button class="btn btn-icone d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarAdmin"
        aria-controls="sidebarAdmin" aria-label="Abrir menu">
        <i class="bi bi-list fs-4"></i>
      </button>
      <button class="btn btn-icone d-none d-lg-inline-flex" type="button" id="btnColapsar" aria-label="Recolher menu">
        <i class="bi bi-list fs-4"></i>
      </button>

      <a class="navbar-brand p-2" href="<?= site_url('admin') ?>">
        <img src="<?= base_url('public/assets/img/logo.png') ?>" alt="Concurso Nacional de Soletração" height="35"
          onerror="this.outerHTML='<span class=\'fw-bold\' style=\'font-family:var(--cns-fonte-display);color:var(--cns-marinho)\'>Soletração</span>'">
      </a>

      <ul class="navbar-nav ms-auto align-items-center gap-1">
        <li class="nav-item">
          <a class="btn btn-icone" href="<?= site_url() ?>" target="_blank" title="Ver o portal público"><i
              class="bi bi-box-arrow-up-right"></i></a>
        </li>
        <?php
        // Notificações internas não lidas (tabela `notificacoes`)
        $naoLidas = db_connect()->table('notificacoes')
          ->where('user_id', auth()->id())->where('lida', 0)->countAllResults();
        ?>
        <li class="nav-item">
          <a class="btn btn-icone position-relative" href="<?= site_url('admin/notificacoes') ?>"
            aria-label="Notificações">
            <i class="bi bi-bell"></i>
            <?php if ($naoLidas > 0): ?>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                style="background:var(--cns-rosa);font-size:.62rem">
                <?= $naoLidas > 9 ? '9+' : $naoLidas ?>
              </span>
            <?php endif ?>
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
            <span
              class="avatar-inicial"><?= esc(mb_strtoupper(mb_substr(auth()->user()->username ?? 'U', 0, 1))) ?></span>
            <span class="d-none d-md-inline"><?= esc(auth()->user()->username ?? '') ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow">
            <li>
              <h6 class="dropdown-header"><?= esc(auth()->user()->username ?? '') ?></h6>
            </li>
            <li><a class="dropdown-item" href="<?= site_url('admin/perfil') ?>"><i class="bi bi-person me-2"></i>O meu
                perfil</a></li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item text-danger" href="<?= site_url('logout') ?>"><i
                  class="bi bi-box-arrow-right me-2"></i>Terminar sessão</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>

  <!-- ===================== SIDEBAR (offcanvas em mobile) ===================== -->
  <aside class="sidebar-admin offcanvas-lg offcanvas-start" tabindex="-1" id="sidebarAdmin"
    aria-label="Menu de administração">
    <div class="offcanvas-header d-lg-none">
      <h5 class="offcanvas-title text-white">Menu</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarAdmin"
        aria-label="Fechar"></button>
    </div>

    <div class="offcanvas-body sidebar-corpo">
      <nav class="nav flex-column p-3">
        <?php /* SIDEBAR ENXUTA: só o que se usa no dia-a-dia.
          Tudo o resto vive em Configurações (admin/sistema). */ ?>

        <a class="nav-link <?= $rota === 'admin' ? 'active' : '' ?>" href="<?= site_url('admin') ?>">
          <i class="bi bi-speedometer2"></i><span class="rotulo">Painel</span>
        </a>

        <?php if (auth()->user()->can('inscricoes.validar', 'inscricoes.criar')): ?>
          <p class="grupo-menu"><span class="rotulo">Inscrições</span></p>
          <a class="nav-link <?= $ativo('admin/inscricoes') ?>" href="<?= site_url('admin/inscricoes') ?>">
            <i class="bi bi-inbox"></i><span class="rotulo">Validar inscrições</span>
          </a>
          <a class="nav-link <?= $ativo('admin/candidatos') ?>" href="<?= site_url('admin/candidatos') ?>">
            <i class="bi bi-search"></i><span class="rotulo">Pesquisar candidatos</span>
          </a>
        <?php endif ?>

        <?php if (auth()->user()->can('concurso.eventos.gerir', 'palavras.gerir')): ?>
          <p class="grupo-menu"><span class="rotulo">Concurso</span></p>
          <?php if (auth()->user()->can('concurso.eventos.gerir')): ?>
            <a class="nav-link <?= $ativo('admin/eventos') ?>" href="<?= site_url('admin/eventos') ?>">
              <i class="bi bi-trophy"></i><span class="rotulo">Eventos</span>
            </a>
            <a class="nav-link <?= $ativo('admin/progressoes') ?>" href="<?= site_url('admin/progressoes') ?>">
              <i class="bi bi-arrow-up-right"></i><span class="rotulo">Progressões</span>
            </a>
          <?php endif ?>
          <?php if (auth()->user()->can('palavras.gerir')): ?>
            <a class="nav-link <?= $ativo('admin/palavras') ?>" href="<?= site_url('admin/palavras') ?>">
              <i class="bi bi-journal-text"></i><span class="rotulo">Banco de palavras</span>
            </a>
          <?php endif ?>
        <?php endif ?>

        <?php if (auth()->user()->can('cms.conteudo.criar')): ?>
          <p class="grupo-menu"><span class="rotulo">Conteúdos</span></p>
          <a class="nav-link <?= $ativo('admin/cms/noticias') ?>" href="<?= site_url('admin/cms/noticias') ?>">
            <i class="bi bi-newspaper"></i><span class="rotulo">Notícias</span>
          </a>
          <a class="nav-link <?= $ativo('admin/cms/media') ?>" href="<?= site_url('admin/cms/media') ?>">
            <i class="bi bi-images"></i><span class="rotulo">Media</span>
          </a>
        <?php endif ?>

        <?php if (auth()->user()->can('inscricoes.validar')): ?>
          <p class="grupo-menu"><span class="rotulo">Análise</span></p>
          <a class="nav-link <?= $ativo('admin/relatorios') ?>" href="<?= site_url('admin/relatorios') ?>">
            <i class="bi bi-bar-chart"></i><span class="rotulo">Relatórios</span>
          </a>
        <?php endif ?>

        <?php /* Porta de entrada para tudo o que é ocasional */ ?>
        <p class="grupo-menu"><span class="rotulo">Sistema</span></p>
        <a class="nav-link <?= $ativo('admin/sistema') ?>" href="<?= site_url('admin/sistema') ?>">
          <i class="bi bi-gear"></i><span class="rotulo">Configurações</span>
        </a>
      </nav>
    </div>
  </aside>

  <!-- ===================== CONTEÚDO ===================== -->
  <main class="conteudo-admin">
    <?= view('components/flash') ?>
    <?= $this->renderSection('conteudo') ?>
  </main>

  <script src="<?= base_url('public/assets/js/bootstrap.bundle.min.js') ?>"></script>
  <script>
    // Colapso da sidebar em desktop (estilo AdminLTE), memorizado na sessão do browser.
    (function () {
      const body = document.body;
      const btn = document.getElementById('btnColapsar');
      if (sessionStorage.getItem('sidebarColapsada') === '1') body.classList.add('sidebar-colapsada');
      btn?.addEventListener('click', () => {
        body.classList.toggle('sidebar-colapsada');
        sessionStorage.setItem('sidebarColapsada', body.classList.contains('sidebar-colapsada') ? '1' : '0');
      });
    })();
  </script>
  <?= $this->renderSection('scripts') ?>
</body>

</html>