<?php /** Rounds do evento: configuração e desempenho de cada um. */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Rounds<?= $this->endSection() ?>
<?= $this->section('conteudo') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <p class="rotulo-secao mb-1"><?= esc($evento->nome) ?></p>
    <h1 class="h3 mb-0">Rounds</h1>
  </div>
  <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/eventos/' . $evento->id) ?>">Voltar</a>
</div>

<div class="cartao">
  <?php if (empty($rounds)): ?>
    <?= view('components/estado_vazio', [
        'palavra'  => 'zero',
        'mensagem' => 'Ainda não há rounds. Eles são abertos no palco, durante o evento.',
    ]) ?>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table tabela-cns align-middle mb-0">
        <thead>
          <tr>
            <th>#</th><th>Tipo</th><th>Dificuldade</th><th class="text-center">Tempo</th>
            <th>Pedidos permitidos</th><th class="text-center">Tentativas</th>
            <th class="text-center">Acertos</th><th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rounds as $r): ?>
            <tr>
              <td class="fw-semibold"><?= (int) $r->numero_round ?></td>
              <td class="small"><?= esc(ucfirst($r->tipo)) ?></td>
              <td class="small"><?= esc(lang('Concurso.dificuldade_' . $r->dificuldade)) ?></td>
              <td class="text-center small"><?= (int) $r->tempo_limite_seg ?>s</td>
              <td class="small texto-suave">
                <?php
                $p = [];
                if ($r->permite_repeticao)  { $p[] = 'Repetição'; }
                if ($r->permite_definicao)  { $p[] = 'Definição'; }
                if ($r->permite_etimologia) { $p[] = 'Etimologia'; }
                if ($r->permite_exemplo)    { $p[] = 'Exemplo'; }
                echo $p ? esc(implode(' · ', $p)) : 'Nenhum';
                ?>
              </td>
              <td class="text-center"><?= (int) $r->tentativas ?></td>
              <td class="text-center">
                <?= (int) $r->acertos ?>
                <?php if ($r->tentativas > 0): ?>
                  <span class="texto-suave small">
                    (<?= round(100 * $r->acertos / $r->tentativas) ?>%)
                  </span>
                <?php endif ?>
              </td>
              <td><?= view('components/badge_estado', ['estado' => $r->status]) ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
