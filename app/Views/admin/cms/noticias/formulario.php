<?php
/**
 * Formulário editorial de notícia (criar/editar) — COMPLETO.
 *
 * Cobre todas as colunas editáveis da tabela `noticias`. Ficam de fora,
 * propositadamente, as que o sistema gere sozinho:
 *   autor_id / editor_id  → quem cria e quem edita
 *   status / data_publicacao / data_agendada → máquina de estados
 *   visualizacoes / tempo_leitura_min → calculados
 *   slug → derivado do título (congelado após publicar)
 */
$eNova = $noticia === null;
$acao = $eNova ? site_url('admin/cms/noticias') : site_url('admin/cms/noticias/' . $noticia->id);
$erros = session('erros');

$v = static fn(string $campo, $omissao = '') => old($campo, $noticia->{$campo} ?? $omissao);
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('titulo') ?><?= $eNova ? 'Nova notícia' : 'Editar notícia' ?><?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <h1 class="h3 mb-0"><?= $eNova ? 'Nova notícia' : 'Editar notícia' ?></h1>
  <div class="d-flex align-items-center gap-2">
    <?php if (!$eNova): ?>
      <?= view('components/badge_estado', ['estado' => $noticia->status]) ?>
      <span class="texto-suave small">
        <?= (int) $noticia->visualizacoes ?> visualizações ·
        <?= (int) $noticia->tempo_leitura_min ?> min de leitura
      </span>
    <?php endif ?>
    <a class="btn btn-cns-contorno btn-sm" href="<?= site_url('admin/cms/noticias') ?>">Voltar</a>
  </div>
</div>

<form method="post" action="<?= esc($acao, 'attr') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">

    <!-- ============ COLUNA PRINCIPAL ============ -->
    <div class="col-lg-8">
      <div class="cartao p-4">
        <?= view('components/campo', [
          'nome' => 'titulo',
          'rotulo' => 'Título',
          'tipo' => 'text',
          'obrigatorio' => true,
          'valor' => $v('titulo'),
          'erros' => $erros
        ]) ?>

        <?= view('components/campo', [
          'nome' => 'subtitulo',
          'rotulo' => 'Subtítulo',
          'tipo' => 'text',
          'valor' => $v('subtitulo'),
          'erros' => $erros
        ]) ?>

        <?= view('components/campo', [
          'nome' => 'resumo',
          'rotulo' => 'Resumo',
          'tipo' => 'textarea',
          'linhas' => 3,
          'valor' => $v('resumo'),
          'erros' => $erros,
          'ajuda' => 'Aparece nas listagens e no SEO se a meta-descrição estiver vazia.'
        ]) ?>

        <?= view('components/campo', [
          'nome' => 'conteudo',
          'rotulo' => 'Conteúdo',
          'tipo' => 'textarea',
          'linhas' => 18,
          'valor' => $v('conteudo'),
          'erros' => $erros,
          'ajuda' => 'HTML permitido (sanitizado ao guardar). Texto simples vira parágrafos.'
        ]) ?>
      </div>

      <!-- SEO -->
      <div class="cartao p-4 mt-3">
        <h2 class="h6 mb-3">SEO e partilha</h2>

        <?= view('components/campo', [
          'nome' => 'meta_titulo',
          'rotulo' => 'Meta-título',
          'tipo' => 'text',
          'valor' => $v('meta_titulo'),
          'erros' => $erros,
          'ajuda' => 'Se vazio, usa o título.'
        ]) ?>

        <?= view('components/campo', [
          'nome' => 'meta_descricao',
          'rotulo' => 'Meta-descrição',
          'tipo' => 'textarea',
          'linhas' => 2,
          'valor' => $v('meta_descricao'),
          'erros' => $erros,
          'ajuda' => 'Se vazia, usa o resumo (máx. 160 caracteres).'
        ]) ?>

        <?= view('components/campo', [
          'nome' => 'meta_keywords',
          'rotulo' => 'Palavras-chave',
          'tipo' => 'text',
          'valor' => $v('meta_keywords'),
          'erros' => $erros,
          'ajuda' => 'Separadas por vírgula.'
        ]) ?>

        <?= view('components/campo', [
          'nome' => 'og_imagem',
          'rotulo' => 'Imagem de partilha (Open Graph)',
          'tipo' => 'text',
          'valor' => $v('og_imagem'),
          'erros' => $erros,
          'ajuda' => 'URL. Se vazia, usa a imagem destacada.'
        ]) ?>
      </div>
    </div>

    <!-- ============ COLUNA LATERAL ============ -->
    <div class="col-lg-4">

      <!-- Publicação -->
      <div class="cartao p-4">
        <h2 class="h6 mb-3">Publicação</h2>

        <button class="btn btn-cns w-100 mb-2" type="submit">
          <i class="bi bi-save me-1"></i> <?= $eNova ? 'Criar rascunho' : 'Guardar alterações' ?>
        </button>

        <?php if (!$eNova && !empty($transicoes)): ?>
          <hr>
          <p class="rotulo-secao mb-2">Ações editoriais</p>
          <?php
          $rotulos = [
            'submeter' => ['Submeter para revisão', 'btn-cns-contorno'],
            'publicar' => ['Publicar agora', 'btn-cns'],
            'agendar' => ['Agendar publicação', 'btn-cns-contorno'],
            'devolver' => ['Devolver a rascunho', 'btn-cns-contorno'],
            'arquivar' => ['Arquivar', 'btn-cns-contorno'],
            'eliminar' => ['Mover para a lixeira', 'btn-cns-deletar'],
            'restaurar' => ['Restaurar', 'btn-cns-contorno'],
          ];
          ?>
          <?php foreach ($transicoes as $transicao => $destino): ?>
            <?php [$rotulo, $classe] = $rotulos[$transicao] ?? [ucfirst($transicao), 'btn-cns-contorno']; ?>

            <?php if ($transicao === 'agendar'): ?>
              <label class="form-label small" for="data_agendada">Data e hora (Luanda)</label>
              <input class="form-control form-control-sm mb-2" type="datetime-local" id="data_agendada" name="data_agendada"
                form="form-agendar">
            <?php endif ?>

            <button class="btn <?= esc($classe, 'attr') ?> w-100 mb-2 btn-sm" type="submit"
              form="form-<?= esc($transicao, 'attr') ?>">
              <?= esc($rotulo) ?>
            </button>
          <?php endforeach ?>
        <?php endif ?>
      </div>

      <!-- Tipo e formato -->
      <div class="cartao p-4 mt-3">
        <h2 class="h6 mb-3">Tipo de conteúdo</h2>

        <?= view('components/campo', [
          'nome' => 'tipo_post',
          'rotulo' => 'Tipo',
          'tipo' => 'select',
          'valor' => $v('tipo_post', 'noticia'),
          'erros' => $erros,
          'opcoes' => [
            'noticia' => 'Notícia',
            'artigo' => 'Artigo',
            'comunicado' => 'Comunicado',
            'reportagem' => 'Reportagem',
            'entrevista' => 'Entrevista',
            'editorial' => 'Editorial',
          ]
        ]) ?>

        <?= view('components/campo', [
          'nome' => 'formato',
          'rotulo' => 'Formato',
          'tipo' => 'select',
          'valor' => $v('formato', 'padrao'),
          'erros' => $erros,
          'opcoes' => [
            'padrao' => 'Padrão',
            'galeria' => 'Galeria',
            'video' => 'Vídeo',
            'audio' => 'Áudio',
            'citacao' => 'Citação',
            'link' => 'Link',
          ]
        ]) ?>
      </div>

      <!-- Imagem destacada -->
      <div class="cartao p-4 mt-3">
        <h2 class="h6 mb-3">Imagem destacada</h2>

        <?php
        $opcoesMedia = ['' => '— Nenhuma —'];
        foreach ($media ?? [] as $m) {
          $opcoesMedia[$m->id] = $m->titulo ?: $m->nome_original;
        }
        ?>
        <select class="form-select mb-2" name="imagem_destacada_id" id="imagem_destacada_id">
          <?php foreach ($opcoesMedia as $id => $nome): ?>
            <option value="<?= esc((string) $id, 'attr') ?>" <?= (string) $v('imagem_destacada_id') === (string) $id ? 'selected' : '' ?>>
              <?= esc($nome) ?>
            </option>
          <?php endforeach ?>
        </select>
        <a class="small" href="<?= site_url('admin/cms/media') ?>" target="_blank">
          Enviar novo ficheiro →
        </a>
      </div>

      <!-- Organização -->
      <div class="cartao p-4 mt-3">
        <h2 class="h6 mb-3">Organização</h2>

        <label class="form-label">Categorias</label>
        <div class="mb-3" style="max-height:160px;overflow-y:auto">
          <?php foreach ($categorias as $cat): ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="categorias[]" value="<?= (int) $cat->id ?>"
                id="cat-<?= (int) $cat->id ?>" <?= in_array($cat->id, $categoriasSelecionadas ?? [], true) ? 'checked' : '' ?>>
              <label class="form-check-label" for="cat-<?= (int) $cat->id ?>"><?= esc($cat->nome) ?></label>
            </div>
          <?php endforeach ?>
          <?php if (empty($categorias)): ?>
            <p class="text-muted small mb-0">
              Sem categorias. <a href="<?= site_url('admin/cms/categorias/nova') ?>">Criar</a>
            </p>
          <?php endif ?>
        </div>

        <?= view('components/campo', [
          'nome' => 'tags',
          'rotulo' => 'Etiquetas',
          'tipo' => 'text',
          'valor' => old('tags', $tagsTexto ?? ''),
          'erros' => $erros,
          'ajuda' => 'Separadas por vírgula. As novas são criadas automaticamente.'
        ]) ?>
      </div>

      <!-- Ligação ao concurso -->
      <div class="cartao p-4 mt-3">
        <h2 class="h6 mb-3">Ligar ao concurso</h2>
        <p class="texto-suave small mb-3">Opcional. Permite filtrar notícias por província, edição ou evento.</p>

        <?= view('components/campo', [
          'nome' => 'provincia_id',
          'rotulo' => 'Província',
          'tipo' => 'select',
          'valor' => $v('provincia_id'),
          'erros' => $erros,
          'opcoes' => $provincias ?? []
        ]) ?>

        <?= view('components/campo', [
          'nome' => 'edicao_id',
          'rotulo' => 'Edição',
          'tipo' => 'select',
          'valor' => $v('edicao_id'),
          'erros' => $erros,
          'opcoes' => $edicoes ?? []
        ]) ?>

        <?= view('components/campo', [
          'nome' => 'evento_id',
          'rotulo' => 'Evento',
          'tipo' => 'select',
          'valor' => $v('evento_id'),
          'erros' => $erros,
          'opcoes' => $eventos ?? []
        ]) ?>
      </div>

      <!-- Visibilidade e opções -->
      <div class="cartao p-4 mt-3">
        <h2 class="h6 mb-3">Visibilidade</h2>

        <?= view('components/campo', [
          'nome' => 'visibilidade',
          'rotulo' => 'Quem pode ver',
          'tipo' => 'select',
          'valor' => $v('visibilidade', 'publica'),
          'erros' => $erros,
          'opcoes' => [
            'publica' => 'Pública',
            'privada' => 'Privada (só a equipa)',
            'protegida_senha' => 'Protegida por senha',
          ]
        ]) ?>

        <div id="bloco-senha">
          <?= view('components/campo', [
            'nome' => 'senha',
            'rotulo' => 'Senha de acesso',
            'tipo' => 'text',
            'valor' => $v('senha'),
            'erros' => $erros,
            'ajuda' => 'Necessária apenas para conteúdo protegido.'
          ]) ?>
        </div>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="destaque" value="1" id="destaque" <?= $v('destaque') ? 'checked' : '' ?>>
          <label class="form-check-label" for="destaque">Destacar na página inicial</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="fixada" value="1" id="fixada" <?= $v('fixada') ? 'checked' : '' ?>>
          <label class="form-check-label" for="fixada">Fixar no topo</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="permitir_comentarios" value="1"
            id="permitir_comentarios" <?= ($eNova || $noticia->permitir_comentarios) ? 'checked' : '' ?>>
          <label class="form-check-label" for="permitir_comentarios">Permitir comentários</label>
        </div>
      </div>
    </div>
  </div>
</form>

<?php /* Formulários independentes para as transições de estado */ ?>
<?php if (!$eNova && !empty($transicoes)): ?>
  <?php foreach ($transicoes as $transicao => $destino): ?>
    <form id="form-<?= esc($transicao, 'attr') ?>" method="post" class="d-none"
      action="<?= site_url('admin/cms/noticias/' . $noticia->id . '/transitar/' . $transicao) ?>">
      <?= csrf_field() ?>
    </form>
  <?php endforeach ?>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  // A senha só faz sentido em conteúdo protegido.
  const vis = document.getElementById('campo-visibilidade');
  const senha = document.getElementById('bloco-senha');
  const alternarSenha = () => { senha.style.display = vis.value === 'protegida_senha' ? '' : 'none'; };
  vis.addEventListener('change', alternarSenha);
  alternarSenha();
</script>
<?= $this->endSection() ?>