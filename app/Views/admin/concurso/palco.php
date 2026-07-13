<?php /* Mesa do júri — condução ao vivo. Consome o PalcoController (JSON/AJAX). */ ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?>Palco<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <h1 class="h3 mb-0"><i class="bi bi-mic me-2"></i>Palco</h1>
  <div class="d-flex gap-2">
    <button class="btn btn-cns-contorno btn-sm" id="btnAbrirRound">Abrir round</button>
    <button class="btn btn-cns-contorno btn-sm" id="btnConcluirRound">Concluir round</button>
    <button class="btn btn-cns btn-sm" id="btnConcluirEvento">Concluir evento</button>
  </div>
</div>

<div id="aviso" class="alert d-none" role="alert"></div>

<div class="row g-3">
  <!-- Sobreviventes -->
  <div class="col-lg-4">
    <div class="cartao p-3 h-100">
      <h2 class="h6 mb-3">Em prova (<span id="nSobreviventes"><?= count($sobreviventes) ?></span>)</h2>
      <div class="list-group list-group-flush" id="listaSobreviventes">
        <?php foreach ($sobreviventes as $s): ?>
          <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-2"
                  data-participacao="<?= (int) $s['id'] ?>">
            <span><span class="texto-suave me-2">#<?= esc($s['numero_concorrente']) ?></span>
                  <?= esc($s['nome_completo']) ?></span>
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
let roundId = null, tentativaId = null;

const aviso = (msg, tipo = 'danger') => {
  const el = document.getElementById('aviso');
  el.className = `alert alert-${tipo}`;
  el.textContent = msg;
  setTimeout(() => el.classList.add('d-none'), 5000);
};

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

  if (!j.sucesso) { aviso(j.erro || 'Erro'); return null; }
  return j;
}

document.getElementById('btnAbrirRound').onclick = async () => {
  const j = await post(`<?= site_url('admin/palco/round/abrir') ?>/${eventoId}`,
                       { tipo: 'eliminatorio', dificuldade: 'media' });
  if (j) { roundId = j.round_id; aviso('Round aberto.', 'success'); }
};

document.querySelectorAll('[data-participacao]').forEach(btn => {
  btn.onclick = async () => {
    if (!roundId) return aviso('Abra um round primeiro.');
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

document.getElementById('btnConcluirRound').onclick = async () => {
  if (!roundId) return aviso('Nenhum round em curso.');
  const j = await post(`<?= site_url('admin/palco/round/concluir') ?>/${roundId}`);
  if (j) { roundId = null; aviso('Round concluído. Recarregue para ver os sobreviventes.', 'success'); }
};

document.getElementById('btnConcluirEvento').onclick = async () => {
  if (!confirm('Concluir o evento e calcular a classificação?')) return;
  const j = await post(`<?= site_url('admin/palco/evento/concluir') ?>/${eventoId}`);
  if (j) location.href = '<?= site_url('admin/eventos') ?>/' + eventoId;
};
</script>
<?= $this->endSection() ?>
