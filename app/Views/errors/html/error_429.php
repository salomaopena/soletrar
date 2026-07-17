<!doctype html>
<html lang="pt-AO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Demasiados pedidos · Concurso Nacional de Soletração</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= base_url('assets/css/tema.css') ?>" rel="stylesheet">
<style>
  body.tela-erro {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: var(--cns-superficie); margin: 0; padding: 1.5rem;
  }
  .cartao-erro { max-width: 480px; text-align: center; }
</style>
</head>
<body class="tela-erro">
  <div class="cartao-erro">
    <span class="fichas justify-content-center mb-4" aria-hidden="true">
      <span class="ficha-letra">4</span><span class="ficha-letra">2</span><span class="ficha-letra">9</span>
    </span>

    <h1 class="h3 mb-2" style="color:var(--cns-marinho)">Devagar, um instante</h1>
    <p class="texto-suave mb-1">
      Foram feitos pedidos a mais num curto espaço de tempo.
    </p>
    <?php if (! empty($tentarDaquiA)): ?>
      <p class="texto-suave mb-4">
        Tente novamente daqui a <strong><?= (int) $tentarDaquiA ?> segundo(s)</strong>.
      </p>
    <?php else: ?>
      <p class="texto-suave mb-4">Aguarde alguns instantes antes de tentar novamente.</p>
    <?php endif ?>

    <a class="btn btn-cns" href="<?= site_url() ?>">
      <i class="bi bi-house me-1"></i> Ir para o início
    </a>
  </div>
</body>
</html>
