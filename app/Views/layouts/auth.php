<?php
/**
 * Layout das telas de autenticação — login, recuperação e redefinição
 * de senha (Fase de personalização do Shield).
 *
 * DESIGN: ecrã dividido. À esquerda, o momento de assinatura da marca —
 * a palavra "SOLETRAR" a surgir letra a letra, com as MESMAS 8 cores do
 * logótipo, na mesma sequência — é literalmente o mecanismo do concurso
 * a acontecer na própria tela de acesso. À direita, o formulário,
 * deliberadamente sóbrio: o momento de cor já aconteceu do outro lado.
 *
 * A animação corre UMA VEZ ao carregar (nunca em loop — é uma tela a
 * que se volta todos os dias; loop seria cansativo, não celebratório).
 * Respeita prefers-reduced-motion (mostra as letras já assentes).
 */

// Legenda da edição ativa — decorativa, nunca pode partir a página.
$edicaoAtual = null;
try {
  $edicaoAtual = model('EdicaoModel')->orderBy('ano', 'DESC')->first();
} catch (\Throwable) {
  $edicaoAtual = null;
}

$palavra = str_split('SOLETRAR');
?>
<!doctype html>
<html lang="pt-AO">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($this->renderSection('titulo') ?: 'Acesso') ?> · Concurso Nacional de Soletração</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800&family=Inter:wght@400;600;700&display=swap"
    rel="stylesheet">
  <link href="<?= base_url('public/assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= base_url('public/assets/css/tema.css') ?>" rel="stylesheet">
  <link rel="apple-touch-icon" href="<?= base_url('public/assets/img/favicon/apple-touch-icon.png') ?>">
  <link rel="shortcut icon" type="image/png" href="<?= base_url('public/assets/img/favicon/favicon-96x96.png') ?>">
  <link type="image/svg+xml" href="<?= base_url('public/assets/img/favicon/favicon.svg') ?>">
  <link rel="manifest" href="<?= base_url('public/assets/img/favicon/site.webmanifest') ?>">
  <style>
    html,
    body {
      height: 100%;
    }

    body.tela-auth {
      margin: 0;
    }

    .grelha-auth {
      min-height: 100vh;
      display: flex;
    }

    @media (max-width: 991.98px) {
      .grelha-auth {
        flex-direction: column;
      }
    }

    /* ---------- Painel de marca (esquerda) ---------- */
    .painel-marca {
      flex: 0 0 46%;
      background: var(--cns-marinho);
      color: #fff;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
      padding: 3rem 2rem;
      /* Textura de caderno escolar — muito subtil, reforça o tema sem competir com o resto. */
      background-image: repeating-linear-gradient(to bottom, transparent 0, transparent 46px, rgba(255, 255, 255, .045) 46px, rgba(255, 255, 255, .045) 47px);
    }

    @media (max-width: 991.98px) {
      .painel-marca {
        flex: 0 0 auto;
        padding: 2.25rem 1.5rem 1.75rem;
      }
    }

    .fichas-login {
      display: inline-flex;
      gap: .4rem;
      flex-wrap: wrap;
      justify-content: center;
    }

    .fichas-login .ficha-letra {
      width: 2.6rem;
      height: 3.05rem;
      font-size: 1.3rem;
      opacity: 0;
      transform: translateY(14px) scale(.85);
      animation: surgir-ficha .55s cubic-bezier(.2, .8, .2, 1) forwards;
      box-shadow: 0 6px 16px rgba(0, 0, 0, .18), inset 0 -3px 0 rgba(0, 0, 0, .14);
    }

    @media (max-width: 991.98px) {
      .fichas-login .ficha-letra {
        width: 2.1rem;
        height: 2.5rem;
        font-size: 1.05rem;
      }
    }

    @keyframes surgir-ficha {
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    @media (prefers-reduced-motion: reduce) {
      .fichas-login .ficha-letra {
        animation: none;
        opacity: 1;
        transform: none;
      }
    }

    .tagline-auth {
      margin: 1.5rem 0 0;
      max-width: 27ch;
      text-align: center;
      color: #C7CBE3;
      font-size: 1.05rem;
      line-height: 1.5;
    }

    .edicao-auth {
      margin-top: .6rem;
      font: 700 .74rem/1 var(--cns-fonte-corpo);
      letter-spacing: .1em;
      text-transform: uppercase;
      color: #8890BE;
    }

    @media (max-width: 575.98px) {

      .tagline-auth,
      .edicao-auth {
        display: none;
      }
    }

    /* ---------- Painel do formulário (direita) ---------- */
    .painel-form {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--cns-superficie);
      padding: 2.5rem 1.5rem;
    }

    .cartao-auth {
      width: 100%;
      max-width: 380px;
      background: var(--cns-branco);
      border: 1px solid var(--cns-borda);
      border-radius: var(--cns-raio);
      box-shadow: 0 20px 50px rgba(29, 36, 51, .08);
      padding: 2.5rem 2.25rem;
    }

    .rodape-auth {
      text-align: center;
      margin-top: 1.25rem;
    }

    .rodape-auth a {
      color: var(--cns-tinta-2);
      text-decoration: none;
      font-size: .85rem;
    }

    .rodape-auth a:hover {
      color: var(--cns-verde-agua);
    }
  </style>
</head>

<body class="tela-auth">
  <div class="grelha-auth">

    <aside class="painel-marca" aria-hidden="true">
      <span class="fichas-login" role="img" aria-label="Soletrar">
        <?php foreach ($palavra as $i => $letra): ?>
          <span class="ficha-letra" style="animation-delay: <?= 0.15 + $i * 0.11 ?>s">
            <?= esc($letra) ?>
          </span>
        <?php endforeach ?>
      </span>

      <p class="tagline-auth">A língua portuguesa em palco, letra a letra.</p>
      <?php if ($edicaoAtual): ?>
        <p class="edicao-auth">Edição <?= (int) $edicaoAtual->ano ?></p>
      <?php endif ?>
    </aside>

    <main class="painel-form">
      <div class="cartao-auth">
        <?= $this->renderSection('conteudo') ?>
      </div>
    </main>

  </div>

  <script src="<?= base_url('public/assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>

</html>