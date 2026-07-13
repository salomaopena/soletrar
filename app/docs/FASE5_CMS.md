# FASE 5 — MÓDULO CMS / NOTÍCIAS (estilo WordPress)
## Concurso Nacional de Soletração — Angola

> Acompanha o pacote `fase5_cms_codigo.zip` com 12 ficheiros PHP prontos
> (a estrutura do zip corresponde a `app/`).

---

## 1. Arquitetura do módulo

```
                       ┌──────────────────────────────┐
 Portal público        │        BACKOFFICE (admin)     │
 ─────────────         │  NoticiasController (CRUD)    │
 NoticiasController    │  MediaController · Menus ...  │
 PaginasController     └──────────────┬────────────────┘
        │                             │
        ▼                             ▼
┌────────────────────────────────────────────────────────┐
│                    CAMADA DE SERVIÇOS                   │
│  NoticiaService ── MaquinaEstadosNoticia (transições)   │
│       │         └─ SanitizadorHtml (HTMLPurifier)       │
│  MediaService ──── UploadService (Fase 4)               │
│  MenuService (cache) · ComentarioService · PaginaService│
└──────────────┬─────────────────────────────────────────┘
               ▼
   NoticiaModel (+ trait Auditavel) · tabelas da Fase 2
               ▼
   Trigger trg_noticias_revisao (histórico automático)
```

Dependência nova: `composer require ezyang/htmlpurifier` (sanitização com
whitelist real — regex não é aceitável para HTML).

## 2. Tabelas envolvidas (Fase 2)

`noticias` (núcleo, com FULLTEXT, soft delete e ligações opcionais a
`provincias`/`edicoes_concurso`/`eventos_competicao`), `noticias_categorias`
(hierárquicas) + `noticias_categorias_rel`, `noticias_tags` + `noticias_tags_rel`,
`noticias_revisoes` (alimentada por trigger), `noticias_comentarios` (threaded),
`media_biblioteca`, `paginas` (hierárquicas), `menus` + `menus_itens`
(já com `noticia_id`, corrigido na v2.0).

## 3. Fluxo editorial — a máquina de estados

Toda a autorização editorial está declarada num único sítio
(`MaquinaEstadosNoticia::TRANSICOES`): cada transição indica o estado de
destino **e** a permissão Shield necessária. Consequências práticas:

- Um **jornalista** (`cms.conteudo.criar`) cria rascunhos, edita os seus e
  **submete** para revisão — não consegue publicar nem que a rota exista.
- Um **editor** (`cms.conteudo.publicar`) aprova, publica, agenda, devolve
  com notas, arquiva e gere a lixeira.
- Os botões do formulário são gerados por `disponiveis($estado)` — a
  interface nunca mostra ações que o utilizador não pode executar, e o
  backend revalida de qualquer forma.

Transições: `submeter`, `publicar`, `agendar`, `devolver`, `arquivar`,
`eliminar` (→ lixeira), `restaurar`. Notificações automáticas: submissão
avisa os editores; publicação e devolução avisam o autor (via `Notificador`
da Fase 3/7).

Regras finas embutidas no `NoticiaService`:

- **Slug estável**: regenerado apenas enquanto a notícia não foi publicada.
  Depois da publicação o título pode mudar, o slug não — links partilhados
  e indexados nunca quebram.
- **Republicação preserva `data_publicacao`** original (ordenação e SEO).
- **Revisões**: delegadas ao trigger de BD `trg_noticias_revisao` — o
  histórico é garantido mesmo que alguém escreva na tabela por fora do
  service.

## 4. Agendamento de publicação

1. Editor escolhe "Agendar" + data/hora **local** (Africa/Luanda).
2. `DataHoraService::deFormulario()` converte para UTC; datas no passado
   são rejeitadas.
3. `php spark cms:publicar-agendados` (cron por minuto) chama
   `publicarAgendadasVencidas()`, que publica tudo o que venceu.
4. O editor também pode publicar manualmente antes da hora (transição
   `agendada → publicada` existe na máquina).

Cron completo do projeto até aqui:

```cron
* * * * * cd /var/www/soletracao && php spark notificacoes:processar   >> /dev/null 2>&1
* * * * * cd /var/www/soletracao && php spark cms:publicar-agendados   >> /dev/null 2>&1
```

## 5. Biblioteca de media

`MediaService` é o único ponto de entrada de ficheiros do CMS, por cima do
`UploadService` da Fase 4 (herda whitelist de MIME real, renomeação
aleatória e re-encode de imagens que remove EXIF/GPS). Acrescenta:

- registo completo em `media_biblioteca` (dimensões, tamanho, `texto_alt`
  para acessibilidade — o formulário de upload torna-o obrigatório);
- miniatura `mini_*.jpg` (360 px) para a grelha do backoffice, sem tocar no
  original;
- **eliminação protegida**: recusa remover media usado como imagem
  destacada (conta e informa quantas notícias o usam); uso dentro do corpo
  apenas gera aviso na interface, como no WordPress.

## 6. Categorias, tags e pesquisa

- Categorias são hierárquicas (parent_id) e atribuídas por checkbox;
  sincronização N:N transacional (`sincronizarCategorias`).
- Tags seguem o comportamento WordPress: o editor escreve nomes livres e as
  inexistentes são criadas na hora (`sincronizarTags`, com `slug_pt`).
- Pesquisa pública usa o índice FULLTEXT `ft_noticia` em modo natural
  language — sem `LIKE '%...%'` a arrastar a tabela.

## 7. Páginas e menus 

- `paginas` cobre conteúdo institucional (Sobre, Regulamento, Contactos),
  com hierarquia, template opcional e o mesmo sanitizador de HTML. O
  `PaginaService` reutiliza a mesma máquina de estados num subconjunto
  (rascunho → publicada → arquivada).
- `MenuService` monta a árvore por localização (`header`, `footer`) numa
  única query com joins, resolve a URL final por tipo de item (página,
  notícia, categoria, externa) e **cacheia** o resultado — o menu é
  renderizado em todas as páginas públicas, não pode custar queries.
  Qualquer escrita administrativa chama `invalidarCache()`.

## 8. Comentários com moderação

Defesa em quatro camadas antes de um comentário chegar ao público:

1. `throttle:5,5` na rota (Fase 4);
2. **honeypot** — campo invisível que, se preenchido, descarta o comentário
   em silêncio (o bot recebe "sucesso" e não aprende);
3. estado inicial `pendente` quando a configuração `comentarios_moderar`
   está ativa **ou** o corpo tem 2+ links (heurística anti-spam);
4. conteúdo guardado como **texto puro** (`strip_tags`) — comentários não
   têm HTML, ponto final.

Editores moderam (`aprovado`/`spam`/`lixeira`) com notificação interna de
pendentes. A leitura pública (`aprovadosDe()`) devolve a árvore threaded já
montada.

## 9. SEO — o que a entity garante

A entity `Noticia` centraliza os fallbacks para as views nunca calcularem
nada: `metaTitulo()` (meta ou título), `metaDescricao()` (meta → resumo →
excerto de 160 chars do conteúdo), `ogImagem()` (og → imagem destacada →
imagem padrão do site), `tempoLeituraMin()`. Completam o SEO:

- URLs canónicas por **slug** (nunca ID), estáveis após publicação;
- `sitemap.xml` e `feed RSS` gerados de `publicas()` (rotas na Fase 9);
- links externos do conteúdo recebem `rel="nofollow"` e `target="_blank"`
  automaticamente no sanitizador.

## 10. Decisões desta fase (e porquê)

| Decisão | Porquê |
|---|---|
| IDs **diretos** no admin do CMS (sem cifra) | Política da Fase 4: a cifra protege dados pessoais; notícias são públicas por natureza. Simplifica o editor (autosave futuro) sem reduzir segurança real |
| HTMLPurifier em vez de regex/strip_tags para o corpo | Whitelist verdadeira com parsing de HTML; regex sobre HTML é uma fonte clássica de bypass XSS |
| Slug congelado após publicação | Nunca quebrar links partilhados/indexados; título continua editável |
| Revisões por trigger de BD (não no service) | Histórico garantido mesmo com escrita fora do service; era o único caso em que o trigger é a camada certa |
| Menu com cache invalidada por escrita | Renderizado em 100 % das páginas públicas — não pode custar queries |
| Comentários como texto puro | HTML em comentários é risco sem benefício editorial |

## 11. Checklist de integração

1. `composer require ezyang/htmlpurifier`.
2. Copiar os ficheiros do zip para `app/`.
3. Registar em `Config/Services.php`: `noticias`, `maquinaEstadosNoticia`,
   `sanitizadorHtml`, `media`, `menus`, `comentarios` (mesmo padrão da Fase 4).
4. Adicionar `texto` aos helpers do `Autoload.php`.
5. Regras nomeadas `guardarNoticia` e `comentarioPublico` em `Config/Validation.php`.
6. Chaves de linguagem `Cms.*` em `Language/pt-AO/Cms.php` (lista nos docblocks).
7. Cron: adicionar `cms:publicar-agendados`.
8. Rotas dos dois controllers (esqueleto nos docblocks; versão completa na Fase 9).
