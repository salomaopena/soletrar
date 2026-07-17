<?php
/**
 * Página de erro genérica (qualquer exceção não tratada especificamente,
 * ex.: erro 500). NUNCA mostra detalhes técnicos — isso é para o log,
 * não para o ecrã. $code pode não vir definido, dependendo de como o
 * CodeIgniter chegou aqui; por isso é sempre tratado como opcional.
 */
$codigo = isset($code) && is_numeric($code) ? (string) $code : null;
?>
<!doctype html>
<html lang="pt-AO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ocorreu um erro · Concurso Nacional de Soletração</title>
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
    <?php if ($codigo && strlen($codigo) <= 3): ?>
      <span class="fichas justify-content-center mb-4" aria-hidden="true">
        <?php foreach (str_split($codigo) as $d): ?>
          <span class="ficha-letra"><?= esc($d) ?></span>
        <?php endforeach ?>
      </span>
    <?php else: ?>
      <i class="bi bi-exclamation-triangle mb-3" style="font-size:2.4rem;color:var(--cns-marinho)"></i>
    <?php endif ?>

    <h1 class="h3 mb-2" style="color:var(--cns-marinho)">Ocorreu um erro</h1>
    <p class="texto-suave mb-4">
      Pedimos desculpa pelo incómodo. A equipa foi notificada e vai analisar o
      que aconteceu. Tente novamente daqui a pouco.
    </p>

    <a class="btn btn-cns" href="<?= site_url() ?>">
      <i class="bi bi-house me-1"></i> Ir para o início
    </a>
  </div>
</body>
</html>
