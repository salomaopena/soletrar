# FASE 8 — FRONTEND COM BOOTSTRAP
## Concurso Nacional de Soletração — Angola

> Acompanha `fase8_frontend_codigo.zip` (tema CSS, 3 layouts, 6 componentes,
> 4 views de página) e `preview_tema.html` — **abra este ficheiro no browser**
> para ver o tema a funcionar com dados de exemplo.

---

## 1. Direção de design

O briefing pede duas coisas em tensão: *minimalismo elegante inspirado na
Apple* e *a paleta de um logotipo lúdico com 8 cores*. A resolução é a tese
do tema:

> **Base disciplinada, cor cirúrgica.** Fundo branco, tipografia forte,
> espaçamento generoso e neutros sóbrios em 95 % da interface; as cores do
> logotipo aparecem apenas onde carregam significado — estados, categorias,
> medalhas e o elemento-assinatura.

**Elemento-assinatura: as fichas de letras (`.ficha-letra`).** A soletração
torna-se linguagem visual: a palavra vencedora renderizada como fichas
coloridas na sequência cromática exata do logotipo (S→verde-água,
O→amarelo, L→verde, E→roxo, T→azul, R→rosa, A→laranja, Ç→marinho, em ciclo
de 8). Usadas no hero, nos estados vazios, nos certificados e nos
resultados — e em mais lado nenhum, para não perderem força.

## 2. Design tokens (`tema.css`)

| Token | Valor | Origem/uso |
|---|---|---|
| `--cns-verde-agua` | `#2AA8A3` | S/O do logo → **cor primária** (botões, links, ativo) |
| `--cns-marinho` | `#232B66` | Ç do logo → títulos, sidebar admin, rodapé |
| `--cns-amarelo` `--cns-verde` `--cns-roxo` `--cns-azul` `--cns-rosa` `--cns-laranja` | do logo | acentos: badges, faixas de stats, fichas, medalhas |
| `--cns-perigo` | `#D6455D` | rosa do logo **escurecido para contraste AA** sobre fundos claros |
| `--cns-tinta` / `--cns-tinta-2` | `#1D2433` / `#5A6478` | texto / texto secundário |
| `--cns-superficie` / `--cns-borda` | `#F7F8FA` / `#E6E9EF` | fundos e separadores |
| Tipografia | **Nunito 700/800** (display) + **Inter** (corpo) | Nunito ecoa as formas arredondadas do lettering do logo sem infantilizar; Inter dá o corpo institucional limpo |
| Forma | raio 14/8 px, sombra suave, botões pill | tom amigável-institucional |

Os tokens fazem ponte com o Bootstrap 5.3 via `--bs-*` (primary, fontes,
raios, links), pelo que os componentes nativos herdam o tema sem
recompilar Sass — decisão deliberada: personalização forte com custo de
build zero, adequada ao hosting alvo.

## 3. Inventário de componentes

| Componente | Ficheiro | Notas |
|---|---|---|
| Flash messages | `components/flash.php` | sucesso/erro/aviso/info + lista de erros de validação |
| Badge de estado | `components/badge_estado.php` | 1 badge para TODOS os estados do domínio (inscrições, notícias, fila); rótulos via `lang()` |
| Campo de formulário | `components/campo.php` | label+input+erro+ajuda num só include; text/select/textarea; `is-invalid` automático a partir de `session('erros')` |
| Cartão de estatística | `components/cartao_stat.php` | faixa de cor da paleta + número em Nunito tabular |
| Estado vazio | `components/estado_vazio.php` | fichas de letras + mensagem orientada à ação ("o que fazer a seguir") |
| Paginação | `components/pager_cns.php` | template do Pager do CI4 (registar em `Config/Pager.php`) |
| Fichas de letras | classes `.fichas`/`.ficha-letra` | assinatura; `--errada` para a letra falhada no palco |
| Grelha de rounds | classes `.grelha-rounds`/`.celula-round` | ✓/✗/apelação/ausente; 1.ª coluna sticky p/ telemóvel |

## 4. Layouts

**Público (`layouts/publico.php`)** — navbar translúcida com blur (o toque
"Apple" do tema), menu dinâmico do `MenuService` (Fase 5, com cache),
slot `meta` para o SEO por página (a entity `Noticia` fornece os
fallbacks), rodapé marinho com newsletter. CTA permanente "Inscrever
candidato".

**Admin (`layouts/admin.php`)** — grid sidebar 250 px + conteúdo sobre
superfície `#F7F8FA`. A sidebar marinho **esconde secções por permissão
Shield** (o menu que se vê é o menu que se pode usar); grupos rotulados
(Concurso/Conteúdos/Sistema); `meta csrf-token` para o AJAX do palco
(Fase 6). Colapsa para 1 coluna abaixo de 992 px.

**E-mail (`emails/layout_base.php`)** — fecha a pendência da Fase 7:
tabelas + estilos inline (regras de cliente de e-mail), 600 px, cabeçalho
marinho com logotipo, sem fontes externas.

## 5. Páginas entregues

- **Resultados de evento** (`publico/resultados/evento.php`) — a
  experiência spellingbee.com/round-results: classificação com pódio
  (medalhas nas cores do logo, linhas destacadas) + grelha de percurso
  round a round, célula por round com a palavra no tooltip, apelações
  aceites visualmente distintas (anel amarelo), legenda, coluna de
  candidato sticky no scroll horizontal.
- **Notícia** (`publico/noticias/ver.php`) — artigo a 720 px/1.75 de
  entrelinha, meta OG completa vinda da entity, comentários com honeypot
  invisível (Fase 5) e aviso honesto de moderação.
- **Dashboard** (`admin/dashboard/index.php`) — 4 stats com faixas de
  cor, inscrições recentes (links já com `rota_segura()` — Fase 4),
  próximos eventos com data destacada.
- **Listagem de inscrições** (`admin/inscricoes/index.php`) — barra de
  estados com contadores (padrão WordPress reutilizado do CMS), tabela
  com badges, estado vazio que explica o âmbito territorial.

## 6. Piso de qualidade embutido

- **Acessibilidade**: `:focus-visible` de 3 px em azul; `aria-label` nas
  células da grelha e nas fichas; honeypot com `aria-hidden`; labels
  sempre presentes (ou `visually-hidden`); contraste AA verificado nos
  pares de badges e no `--cns-perigo`.
- **`prefers-reduced-motion`** respeitado globalmente.
- **Responsivo**: grelha de rounds com scroll horizontal + coluna sticky;
  admin colapsa; hero com `clamp()`.
- **Escape**: `esc()` em toda a saída; a única exceção é
  `$noticia->conteudo`, comentada e coberta pelo sanitizador da Fase 5.

## 7. Checklist de integração

1. Copiar `public/assets/css/tema.css` e as views para o projeto; colocar
   `logo.png` e `logo-email.png` em `public/assets/img/`.
2. Registar o template do pager em `Config/Pager.php`
   (`'default_full' => 'components/pager_cns'`).
3. Chaves `Geral.estado_*` em `Language/pt-AO/Geral.php`
   (pendente, validada, rejeitada, rascunho, revisao, agendada,
   publicada, em_curso, falhada...).
4. Helper `data_exibir()` com os formatos `curta|longa|hora|dia|mes`
   (DataHoraService — Fase 3; código final na Fase 9).
5. Produção: servir Bootstrap/fontes localmente (`npm i bootstrap` →
   copiar dist) em vez de CDN, para funcionar com conectividade
   intermitente — recomendado para o contexto de uso em eventos.
