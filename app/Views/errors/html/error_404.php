<!doctype html>
<html lang="pt-AO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Página não encontrada · Concurso Nacional de Soletração</title>
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
      <span class="ficha-letra">4</span><span class="ficha-letra">0</span><span class="ficha-letra">4</span>
    </span>

    <h1 class="h3 mb-2" style="color:var(--cns-marinho)">Página não encontrada</h1>
    <p class="texto-suave mb-4">
      <?= esc($message ?? 'O recurso pedido não existe ou o link é inválido.') ?>
    </p>

    <div class="d-flex gap-2 justify-content-center flex-wrap">
      <a class="btn btn-cns" href="<?= site_url() ?>">
        <i class="bi bi-house me-1"></i> Ir para o início
      </a>
      <a class="btn btn-cns-contorno" href="<?= site_url('resultados') ?>">
        <i class="bi bi-trophy me-1"></i> Ver resultados
      </a>
    </div>
  </div>
</body>
</html>
