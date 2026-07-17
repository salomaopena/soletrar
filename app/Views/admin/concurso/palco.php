<?php /* Mesa do júri — condução ao vivo. Consome o PalcoController (JSON/AJAX). */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Palco<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h1 class="h3 mb-0"><i class="bi bi-mic me-2"></i>Palco</h1>
  <div class="d-flex gap-2">
    <div class="dropdown">
      <button class="btn btn-cns-contorno btn-sm dropdown-toggle" type="button"
              id="btnAbrirRound" data-bs-toggle="dropdown" <?= $roundAtual ? 'disabled' : '' ?>>
        Abrir round
      </button>
      <div class="dropdown-menu p-3" style="min-width:280px">
        <label class="form-label small" for="selTipoRound">Tipo de round</label>
        <select class="form-select form-select-sm mb-2" id="selTipoRound">
          <option value="eliminatorio">Eliminatório</option>
          <option value="classificatorio">Classificatório</option>
          <option value="desempate">Desempate</option>
          <option value="final">Final</option>
        </select>
        <label class="form-label small" for="selDificuldadeRound">Dificuldade</label>
        <select class="form-select form-select-sm mb-3" id="selDificuldadeRound">
          <option value="muito_facil">Muito fácil</option>
          <option value="facil">Fácil</option>
          <option value="media" selected>Média</option>
          <option value="dificil">Difícil</option>
          <option value="muito_dificil">Muito difícil</option>
        </select>
        <button class="btn btn-cns btn-sm w-100" type="button" id="btnConfirmarAbrirRound">
          Confirmar abertura
        </button>
        <p class="form-text mt-2 mb-0">
          «Desempate»: use quando a homologação bloquear por empate nas vagas de qualificação.
        </p>
      </div>
    </div>
    <button class="btn btn-cns-contorno btn-sm" id="btnConcluirRound" <?= $roundAtual ? '' : 'disabled' ?>>
      Concluir round
    </button>
    <button class="btn btn-cns btn-sm" id="btnConcluirEvento">Concluir evento</button>
  </div>
</div>

<?php /* Estado do round — lido da BD ao carregar a página (nunca só de JS). */ ?>
<div id="faixaRound" class="alert <?= $roundAtual ? 'alert-info' : 'alert-secondary' ?> py-2 mb-3">
  <?php if ($roundAtual): ?>
    <i class="bi bi-record-circle text-danger me-1"></i>
    Round <strong>#<?= (int) $roundAtual->numero_round ?></strong> em curso ·
    dificuldade <strong><?= esc(lang('Concurso.dificuldade_' . $roundAtual->dificuldade)) ?></strong> ·
    <span id="contadorTentativas"><?= count($jaTentaramNoRound) ?></span> soletração(ões) já registada(s)
  <?php else: ?>
    <i class="bi bi-pause-circle me-1"></i> Nenhum round em curso. Clique em «Abrir round» para começar.
  <?php endif ?>
</div>

<div id="aviso" class="alert d-none" role="alert"></div>

<div class="row g-3">
  <!-- Sobreviventes -->
  <div class="col-lg-4">
    <div class="cartao p-3 h-100">
      <h2 class="h6 mb-3">Em prova (<span id="nSobreviventes"><?= count($sobreviventes) ?></span>)</h2>
      <div class="list-group list-group-flush" id="listaSobreviventes">
        <?php $jaTentou = array_column($jaTentaramNoRound, 'participacao_id'); ?>
        <?php foreach ($sobreviventes as $s): ?>
          <?php $feito = in_array((int) $s['id'], $jaTentou, true); ?>
          <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-2 botao-candidato"
                  data-participacao="<?= (int) $s['id'] ?>" <?= $feito ? 'data-ja-tentou="1"' : '' ?>>
            <span><span class="texto-suave me-2">#<?= esc($s['numero_concorrente']) ?></span>
                  <?= esc($s['nome_completo']) ?>
                  <?php if ($feito): ?>
                    <i class="bi bi-check-circle-fill text-success ms-1" title="Já soletrou neste round"></i>
                  <?php endif ?>
            </span>
            <span class="badge text-bg-light"><?= (int) $s['pontuacao_total'] ?> pts</span>
          </button>
        <?php endforeach ?>
      </div>
      <p class="form-text mt-2 mb-0">Clique num candidato para lhe entregar uma palavra.</p>
    </div>
  </div>

  <!-- Cartão do pronunciador -->
  <div class="col-lg-8">
    <div class="cartao p-4 h-100">
      <div id="semPalavra" class="estado-vazio py-5">
        <span class="fichas" aria-hidden="true">
          <?php foreach (str_split('pronto') as $l): ?><span class="ficha-letra"><?= $l ?></span><?php endforeach ?>
        </span>
        <p class="mb-0">Abra um round e chame um candidato.</p>
      </div>

      <div id="cartaoPalavra" class="d-none">
        <p class="rotulo-secao mb-1">Palavra a soletrar</p>
        <h2 class="display-6 fw-bold mb-1" id="pPalavra" style="font-family:var(--cns-fonte-display);color:var(--cns-marinho)"></h2>
        <p class="texto-suave mb-3" id="pSilabacao"></p>

        <dl class="row small">
          <dt class="col-sm-3">Definição</dt>   <dd class="col-sm-9" id="pDefinicao"></dd>
          <dt class="col-sm-3">Exemplo</dt>     <dd class="col-sm-9" id="pExemplo"></dd>
          <dt class="col-sm-3">Etimologia</dt>  <dd class="col-sm-9" id="pEtimologia"></dd>
          <dt class="col-sm-3">Notas</dt>       <dd class="col-sm-9 text-danger" id="pNotas"></dd>
        </dl>

        <div class="d-flex flex-wrap gap-2 my-3">
          <?php foreach (['repeticao' => 'Repetição', 'definicao' => 'Definição',
                          'etimologia' => 'Etimologia', 'exemplo' => 'Exemplo'] as $k => $r): ?>
            <button class="btn btn-sm btn-outline-secondary btn-pedido" data-pedido="<?= $k ?>">
              Pediu <?= $r ?>
            </button>
          <?php endforeach ?>
        </div>

        <hr>
        <label class="form-label" for="respostaDada">Soletração dada pelo candidato</label>
        <input class="form-control form-control-lg mb-3" id="respostaDada"
               placeholder="Escreva as letras ditas" autocomplete="off">

        <div class="d-flex gap-2">
          <button class="btn btn-cns flex-fill" id="btnCerto">
            <i class="bi bi-check-lg me-1"></i> Correto
          </button>
          <button class="btn btn-outline-danger flex-fill" id="btnErrado">
            <i class="bi bi-x-lg me-1"></i> Incorreto
          </button>
        </div>
        <p class="form-text mt-2">
          A decisão do júri prevalece sobre qualquer sugestão do sistema.
        </p>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const eventoId = <?= (int) $eventoId ?>;
const csrf = document.querySelector('meta[name="csrf-token"]').content;

// ESTADO INICIAL LIDO DO SERVIDOR — é o que faltava. Sem isto, recarregar
// a página (ou abrir noutro separador) esquecia que já havia um round
// aberto, e «Abrir round» respondia «já existe» sem o ecrã reagir.
let roundId     = <?= $roundAtual->id ?? 'null' ?>;
let tentativaId = null;

const btnAbrir    = document.getElementById('btnAbrirRound');
const btnConcluir = document.getElementById('btnConcluirRound');
const faixaRound  = document.getElementById('faixaRound');

const aviso = (msg, tipo = 'danger') => {
  const el = document.getElementById('aviso');
  el.className = `alert alert-${tipo}`;
  el.textContent = msg;
  el.classList.remove('d-none');
  setTimeout(() => el.classList.add('d-none'), 5000);
};

/** Mantém os botões e a faixa de estado sincronizados com roundId. */
function atualizarEstadoRound(info = null) {
  btnAbrir.disabled    = roundId !== null;
  btnConcluir.disabled = roundId === null;

  if (roundId === null) {
    faixaRound.className = 'alert alert-secondary py-2 mb-3';
    faixaRound.innerHTML = '<i class="bi bi-pause-circle me-1"></i> '
      + 'Nenhum round em curso. Clique em «Abrir round» para começar.';
  } else if (info) {
    faixaRound.className = 'alert alert-info py-2 mb-3';
    faixaRound.innerHTML = `<i class="bi bi-record-circle text-danger me-1"></i> `
      + `Round <strong>#${info.numero_round}</strong> em curso · `
      + `dificuldade <strong>${info.dificuldade}</strong>`;
  }
}

// Envia como form-data com o token CSRF em header (Config\Security::headerName).
async function post(url, dados = {}) {
  const corpo = new URLSearchParams(dados);

  const r = await fetch(url, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrf,
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: corpo,
  });

  let j;
  try { j = await r.json(); } catch { aviso('Resposta inesperada do servidor.'); return null; }

  if (!j.sucesso) {
    const msg = j.erro || 'Erro';

    // Se o servidor disser «já há um round em curso», a página estava
    // com o estado desatualizado — a solução é sincronizar, não insistir.
    if (msg.toLowerCase().includes('já existe um round')) {
      aviso('Já havia um round em curso — a atualizar o ecrã. Recarregue se precisar.', 'warning');
      setTimeout(() => location.reload(), 1500);
      return null;
    }

    // Conjunto de palavras esgotado nesta dificuldade: dizer O QUE FAZER,
    // não só que acabou.
    if (msg.toLowerCase().includes('esgotou')) {
      const el = document.getElementById('aviso');
      el.className = 'alert alert-warning';
      el.innerHTML = msg
        + ' — <a href="<?= site_url('admin/eventos/' . $eventoId . '/pool') ?>" target="_blank">'
        + 'adicione mais palavras a essa dificuldade</a>, escolha outra dificuldade ao abrir o'
        + ' próximo round, ou conclua o round agora com os candidatos já avaliados.';
      el.classList.remove('d-none');
      setTimeout(() => el.classList.add('d-none'), 9000);
      return null;
    }

    aviso(msg);
    return null;
  }
  return j;
}

document.getElementById('btnConfirmarAbrirRound').onclick = async () => {
  const tipo        = document.getElementById('selTipoRound').value;
  const dificuldade = document.getElementById('selDificuldadeRound').value;

  const j = await post(`<?= site_url('admin/palco/round/abrir') ?>/${eventoId}`, { tipo, dificuldade });
  if (j) {
    roundId = j.round_id;
    atualizarEstadoRound({ numero_round: '?', dificuldade });
    aviso('Round aberto (' + tipo + '). Já pode chamar um candidato.', 'success');
    bootstrap.Dropdown.getOrCreateInstance(btnAbrir).hide();
  }
};

document.querySelectorAll('[data-participacao]').forEach(btn => {
  btn.onclick = async () => {
    if (roundId === null) return aviso('Abra um round primeiro.');

    const j = await post(`<?= site_url('admin/palco/vez') ?>/${roundId}/${btn.dataset.participacao}`);
    if (!j) return;

    tentativaId = j.tentativa_id;
    const p = j.palavra;
    document.getElementById('pPalavra').textContent    = p.texto;
    document.getElementById('pSilabacao').textContent  = p.silabacao || '';
    document.getElementById('pDefinicao').textContent  = p.definicao || '—';
    document.getElementById('pExemplo').textContent    = p.exemplo || '—';
    document.getElementById('pEtimologia').textContent = p.etimologia || '—';
    document.getElementById('pNotas').textContent      = p.notas || '';
    document.getElementById('semPalavra').classList.add('d-none');
    document.getElementById('cartaoPalavra').classList.remove('d-none');
    document.getElementById('respostaDada').value = '';
    document.getElementById('respostaDada').focus();
  };
});

document.querySelectorAll('.btn-pedido').forEach(b => {
  b.onclick = () => tentativaId &&
    post(`<?= site_url('admin/palco/tentativa') ?>/${tentativaId}/pedido`, { pedido: b.dataset.pedido });
});

const avaliar = async (correta) => {
  if (!tentativaId) return;
  const j = await post(`<?= site_url('admin/palco/tentativa') ?>/${tentativaId}/avaliar`, {
    resposta_dada: document.getElementById('respostaDada').value,
    correta: correta ? '1' : '0',
  });
  if (j) {
    aviso(correta ? 'Registado: correto.' : 'Registado: incorreto.', correta ? 'success' : 'warning');
    document.getElementById('cartaoPalavra').classList.add('d-none');
    document.getElementById('semPalavra').classList.remove('d-none');
    tentativaId = null;
  }
};
document.getElementById('btnCerto').onclick   = () => avaliar(true);
document.getElementById('btnErrado').onclick  = () => avaliar(false);

btnConcluir.onclick = async () => {
  if (roundId === null) return aviso('Nenhum round em curso.');

  const j = await post(`<?= site_url('admin/palco/round/concluir') ?>/${roundId}`);
  if (j) {
    roundId = null;
    atualizarEstadoRound();
    aviso('Round concluído.', 'success');
    setTimeout(() => location.reload(), 1200);   // atualiza sobreviventes
  }
};

document.getElementById('btnConcluirEvento').onclick = async () => {
  if (!confirm('Concluir o evento e calcular a classificação?')) return;
  const j = await post(`<?= site_url('admin/palco/evento/concluir') ?>/${eventoId}`);
  if (j) location.href = '<?= site_url('admin/eventos') ?>/' + eventoId;
};

// Estado inicial dos botões, já correto ao carregar a página.
atualizarEstadoRound(<?= $roundAtual ? json_encode(['numero_round' => $roundAtual->numero_round, 'dificuldade' => lang('Concurso.dificuldade_' . $roundAtual->dificuldade)]) : 'null' ?>);
</script>
<?= $this->endSection() ?>
