<?php /* Página pública de resultados de um evento.
   Dados: $evento, $classificacao (RelatorioService::classificacaoEvento),
          $grelha (RelatorioService::grelhaRounds), $totalRounds.
   Experiência de referência: spellingbee.com/round-results. */ ?>
<?= $this->extend('layouts/publico') ?>
<?= $this->section('titulo') ?>Resultados · <?= esc($evento->nome) ?><?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="container py-5">

  <p class="rotulo-secao mb-2"><?= esc($evento->tipo_fase_rotulo ?? 'Resultados') ?></p>
  <h1 class="mb-1"><?= esc($evento->nome) ?></h1>
  <p class="texto-suave mb-5">
    <?= esc(data_exibir($evento->data_evento, 'longa')) ?> · <?= esc($evento->local ?? '') ?>
  </p>

  <!-- Pódio / classificação final -->
  <section class="cartao p-4 mb-5" aria-labelledby="titulo-classificacao">
    <h2 id="titulo-classificacao" class="h4 mb-4">Classificação final</h2>
    <div class="table-responsive">
      <table class="table tabela-cns align-middle mb-0">
        <thead>
          <tr>
            <th scope="col" style="width:4rem">Pos.</th>
            <th scope="col">Candidato</th>
            <th scope="col">Escola</th>
            <th scope="col" class="text-center">Classe</th>
            <th scope="col" class="text-center">Pontos</th>
            <th scope="col" class="text-center">Sobreviveu até</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($classificacao as $linha): ?>
            <tr class="<?= $linha['posicao_final'] <= 3 ? 'linha-podio' : '' ?>">
              <td>
                <?php if ($linha['posicao_final'] <= 3): ?>
                  <span class="medalha medalha--<?= (int) $linha['posicao_final'] ?>">
                    <?= (int) $linha['posicao_final'] ?>
                  </span>
                <?php else: ?>
                  <?= (int) $linha['posicao_final'] ?>.º
                <?php endif ?>
              </td>
              <td class="fw-semibold"><?= esc($linha['nome_completo']) ?></td>
              <td class="texto-suave"><?= esc($linha['escola']) ?></td>
              <td class="text-center"><?= (int) $linha['classe_atual'] ?>.ª</td>
              <td class="text-center fw-semibold"><?= (int) $linha['pontuacao_total'] ?></td>
              <td class="text-center">
                <?= $linha['eliminado_round'] === null
                    ? '<span class="badge-estado badge-estado--validada">Final</span>'
                    : esc($linha['eliminado_round']) . '.º round' ?>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Percurso round a round -->
  <section class="cartao p-4" aria-labelledby="titulo-rounds">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <h2 id="titulo-rounds" class="h4 mb-0">Percurso por round</h2>
      <ul class="list-inline small texto-suave mb-0">
        <li class="list-inline-item"><span class="celula-round celula-round--acerto">✓</span> acertou</li>
        <li class="list-inline-item"><span class="celula-round celula-round--erro">✗</span> eliminado</li>
        <li class="list-inline-item"><span class="celula-round celula-round--apelacao">✓</span> por apelação</li>
        <li class="list-inline-item"><span class="celula-round celula-round--ausente">–</span> não soletrou</li>
      </ul>
    </div>

    <div class="grelha-rounds">
      <table>
        <thead>
          <tr>
            <th class="col-candidato" scope="col">Candidato</th>
            <?php for ($r = 1; $r <= $totalRounds; $r++): ?>
              <th scope="col">R<?= $r ?></th>
            <?php endfor ?>
          </tr>
        </thead>
        <tbody>
          <?php /* $grelha reorganizada pelo controller:
                   [participacao_id => ['nome' => ..., 'rounds' => [n => tentativa|null]]] */ ?>
          <?php foreach ($grelha as $candidato): ?>
            <tr>
              <td class="col-candidato">
                <span class="texto-suave me-2">#<?= esc($candidato['numero_concorrente']) ?></span>
                <?= esc($candidato['nome']) ?>
              </td>
              <?php for ($r = 1; $r <= $totalRounds; $r++): ?>
                <td>
                  <?php $t = $candidato['rounds'][$r] ?? null; ?>
                  <?php if ($t === null): ?>
                    <span class="celula-round celula-round--ausente" aria-label="Não soletrou">–</span>
                  <?php elseif ($t['apelacao_resultado'] === 'aceite'): ?>
                    <span class="celula-round celula-round--apelacao"
                          title="<?= esc($t['palavra'], 'attr') ?> (decidido por apelação)"
                          aria-label="Acertou por apelação">✓</span>
                  <?php elseif ((int) $t['correta'] === 1): ?>
                    <span class="celula-round celula-round--acerto"
                          title="<?= esc($t['palavra'], 'attr') ?>" aria-label="Acertou">✓</span>
                  <?php else: ?>
                    <span class="celula-round celula-round--erro"
                          title="<?= esc($t['palavra'], 'attr') ?>" aria-label="Eliminado">✗</span>
                  <?php endif ?>
                </td>
              <?php endfor ?>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <p class="small texto-suave mt-3 mb-0">
      Passe o cursor sobre uma célula para ver a palavra soletrada.
    </p>
  </section>

</div>
<?= $this->endSection() ?>
