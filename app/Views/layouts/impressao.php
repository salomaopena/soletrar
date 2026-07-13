<?php
/**
 * Layout para impressão (listas, pautas, comprovativos).
 * Sem menus, sem cores fortes, com cabeçalho e rodapé institucionais.
 * Abre a caixa de impressão automaticamente.
 */
?>
<!doctype html>
<html lang="pt-AO">
<head>
  <meta charset="utf-8">
  <title><?= esc($titulo ?? 'Impressão') ?></title>
  <style>
    /* ---------- Impressão ---------- */
    @page { size: A4 landscape; margin: 14mm 10mm; }

    body {
      font-family: "Helvetica Neue", Arial, sans-serif;
      color: #1D2433; font-size: 11px; margin: 0;
    }

    .cabecalho {
      display: flex; justify-content: space-between; align-items: flex-end;
      border-bottom: 2px solid #232B66; padding-bottom: 8px; margin-bottom: 12px;
    }
    .cabecalho h1 { margin: 0; font-size: 16px; color: #232B66; }
    .cabecalho .sub { font-size: 10px; color: #5A6478; margin-top: 2px; }
    .cabecalho .meta { font-size: 10px; color: #5A6478; text-align: right; }

    table { width: 100%; border-collapse: collapse; }
    thead { display: table-header-group; }        /* repete o cabeçalho em cada página */
    tr { page-break-inside: avoid; }
    th {
      text-align: left; font-size: 9px; text-transform: uppercase;
      letter-spacing: .04em; color: #5A6478;
      border-bottom: 1.5px solid #232B66; padding: 5px 4px;
    }
    td { padding: 5px 4px; border-bottom: .5px solid #E6E9EF; }
    tbody tr:nth-child(even) { background: #FAFBFC; }

    .assinatura { width: 90px; border-bottom: .5px solid #999; }

    .rodape {
      margin-top: 14px; padding-top: 6px; border-top: .5px solid #E6E9EF;
      font-size: 9px; color: #5A6478; display: flex; justify-content: space-between;
    }

    .filtros-aplicados {
      font-size: 10px; color: #5A6478; margin-bottom: 8px;
      background: #F7F8FA; padding: 6px 8px; border-radius: 4px;
    }

    /* Botões só no ecrã, nunca no papel */
    .barra-ecra { margin-bottom: 12px; }
    @media print { .barra-ecra { display: none !important; } }
  </style>
</head>
<body>

<div class="barra-ecra">
  <button onclick="window.print()">Imprimir</button>
  <button onclick="window.close()">Fechar</button>
</div>

<div class="cabecalho">
  <div>
    <h1>Concurso Nacional de Soletração | Angola</h1>
    <div class="sub"><?= esc($titulo ?? '') ?></div>
  </div>
  <div class="meta">
    Emitido em <?= esc(data_exibir(utc_agora(), 'curta_hora')) ?><br>
    Por <?= esc(auth()->user()->username ?? '') ?>
  </div>
</div>

<?= $this->renderSection('conteudo') ?>

<div class="rodape">
  <span>Documento gerado pelo sistema do Concurso Nacional de Soletração.</span>
  <span><?= esc(data_exibir(utc_agora(), 'curta')) ?></span>
</div>

<script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
