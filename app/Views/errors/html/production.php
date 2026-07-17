<?php
/**
 * Última linha de defesa — mostrada quando algo corre mal de uma forma
 * grave o suficiente para não passar pelas outras views de erro.
 *
 * DELIBERADAMENTE sem nenhuma dependência externa: sem CDN, sem Google
 * Fonts, sem tema.css. Se o servidor estiver com problemas de rede, DNS,
 * ou até sem ligação à internet, esta página TEM de continuar a
 * aparecer com bom aspeto. Cores da marca escritas em hexadecimal fixo,
 * "fichas" simuladas só com CSS inline, tipografia do sistema.
 *
 * Nenhuma variável é assumida como definida — tudo tem valor por omissão.
 */
$codigo = isset($code) && is_numeric($code) ? (string) $code : '';
?>
<!doctype html>
<html lang="pt-AO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ocorreu um erro</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: #F7F8FA; padding: 1.5rem;
    font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
    color: #1D2433;
  }
  .cartao { max-width: 440px; text-align: center; }
  .fichas { display: inline-flex; gap: .3rem; margin-bottom: 1.5rem; }
  .ficha {
    width: 2.3rem; height: 2.7rem; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: 1.1rem;
    box-shadow: inset 0 -3px 0 rgba(0,0,0,.15);
  }
  h1 { font-size: 1.35rem; margin: 0 0 .5rem; color: #232B66; }
  p { color: #5A6478; font-size: .95rem; line-height: 1.6; margin: 0 0 1.5rem; }
  a.botao {
    display: inline-block; padding: .6rem 1.5rem; border-radius: 999px;
    background: #2AA8A3; color: #fff; text-decoration: none; font-weight: 700; font-size: .9rem;
  }
</style>
</head>
<body>
  <div class="cartao">
    <?php if ($codigo !== '' && strlen($codigo) <= 3): ?>
      <div class="fichas" aria-hidden="true">
        <?php
        $cores = ['#2AA8A3', '#F5B921', '#8CBF2F', '#8A3FB5', '#2D7FD3', '#EF5B92', '#F07830'];
        foreach (str_split($codigo) as $i => $d):
        ?>
          <span class="ficha" style="background:<?= esc($cores[$i % count($cores)], 'attr') ?>"><?= esc($d) ?></span>
        <?php endforeach ?>
      </div>
    <?php endif ?>

    <h1>Ocorreu um erro</h1>
    <p>Pedimos desculpa pelo incómodo. A equipa foi notificada. Tente novamente daqui a pouco.</p>

    <a class="botao" href="/">Ir para o início</a>
  </div>
</body>
</html>
