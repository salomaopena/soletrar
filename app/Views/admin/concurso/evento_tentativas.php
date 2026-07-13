<?php /** Histórico de tentativas do evento, round a round. */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Tentativas<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <p class="rotulo-secao mb-1"><?= esc($evento->nome) ?></p>
    <h1 class="h3 mb-0">Histórico de tentativas</h1>
  </div>
  <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/eventos/' . $evento->id) ?>">Voltar</a>
</div>

<div class="cartao">
  <?php if (empty($tentativas)): ?>
    <?= view('components/estado_vazio', [
        'palavra'  => 'zero',
        'mensagem' => 'Ainda não há tentativas registadas neste evento.',
    ]) ?>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table tabela-cns align-middle mb-0">
        <thead><tr><th>R</th><th>Concorrente</th><th>Palavra</th><th>Resposta</th>
                   <th class="text-center">Resultado</th><th class="text-center">Pedidos</th>
                   <th class="text-center">Tempo</th><th class="text-center">Pts</th></tr></thead>
        <tbody>
          <?php foreach ($tentativas as $t): ?>
            <tr>
              <td class="texto-suave"><?= (int) $t->numero_round ?></td>
              <td><span class="texto-suave me-1">#<?= esc($t->numero_concorrente) ?></span>
                  <?= esc($t->nome_completo) ?></td>
              <td class="fw-semibold"><?= esc($t->palavra) ?></td>
              <td class="small texto-suave"><?= esc($t->resposta_dada ?? '—') ?></td>
              <td class="text-center">
                <?php if ($t->apelacao_resultado === 'aceite'): ?>
                  <span class="celula-round celula-round--apelacao" title="Aceite por apelação">✓</span>
                <?php elseif ($t->correta === null): ?>
                  <span class="texto-suave">—</span>
                <?php elseif ((int) $t->correta === 1): ?>
                  <span class="celula-round celula-round--acerto">✓</span>
                <?php else: ?>
                  <span class="celula-round celula-round--erro">✗</span>
                <?php endif ?>
              </td>
              <td class="text-center small texto-suave">
                <?php
                $pedidos = [];
                if ($t->pediu_repeticao)  { $pedidos[] = 'Rep'; }
                if ($t->pediu_definicao)  { $pedidos[] = 'Def'; }
                if ($t->pediu_etimologia) { $pedidos[] = 'Eti'; }
                if ($t->pediu_exemplo)    { $pedidos[] = 'Exe'; }
                echo $pedidos ? esc(implode(' · ', $pedidos)) : '—';
                ?>
              </td>
              <td class="text-center small"><?= (int) $t->tempo_resposta_seg ?>s</td>
              <td class="text-center fw-semibold"><?= (int) $t->pontos_atribuidos ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
