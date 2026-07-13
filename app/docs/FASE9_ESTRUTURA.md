# FASE 9 — ESTRUTURA INICIAL DO CÓDIGO
## Concurso Nacional de Soletração — Angola

> Acompanha `fase9_estrutura_codigo.zip`. Esta fase amarra tudo: resolve as
> pendências que as fases 3–8 referenciavam, entrega as rotas e migrations,
> e demonstra o primeiro fluxo ponta-a-ponta (inscrição pública).

---

## 1. Pendências resolvidas (o que faltava para o código correr)

Ao longo das fases, vários componentes foram *referenciados* antes de
existirem. A Fase 9 fecha-os:

| Referenciado em | Componente entregue agora |
|---|---|
| Todas (datas) | `DataHoraService` + `data_helper` (`utc_agora`, `data_exibir`, `idade`) |
| Fase 6 (uuid) | `UuidService` (v4 sem dependência externa) |
| Fases 4/6/7 (`service('configuracao')`) | `ConfiguracaoService` (cache + cast por tipo) |
| Fases 4/6 (`$this->escopo`) | `AdminBaseController` + `EscopoProvincialFilter` |
| Fases 5/9 (público) | `BaseController` (helpers globais) |
| Todas | `Config/Services.php` **consolidado** (37 registos) |
| Fase 3 | `Routes.php` dividido em `publico/auth/admin` |
| Fase 2 | Migrations de exemplo + ordem canónica + seeder das 21 províncias |

## 2. Rotas

O `Routes.php` apenas inclui três ficheiros por área (decisão da Fase 3),
mantendo cada conjunto legível:

- **`publico.php`** — home, notícias (slug, com a rota curinga `(:segment)`
  em último para não capturar `categoria/`, `tag/`), páginas, resultados,
  inscrição pública (com `throttle:3,10`), newsletter, `sitemap.xml`/`feed`.
- **`auth.php`** — `service('auth')->routes()` do Shield + o webhook
  `api/sms/callback` (no grupo `api/*` isento de CSRF, como definido na
  Fase 4).
- **`admin.php`** — grupo `admin` com a cadeia de filtros
  `session → escopo → auditoria`, e **cada subgrupo com a sua permissão**
  (`permission:inscricoes.validar`, `permission:cms.conteudo.publicar`,
  `permission:concurso.resultados.homologar`...). É aqui que a matriz de
  permissões da Fase 3 encontra as rotas concretas.

A ordem dos filtros importa e está fixada: o Shield autentica, o
`EscopoProvincialFilter` resolve o território (e barra coordenadores sem
atribuição), e o `AuditoriaFilter` regista no fim.

## 3. Migrations

**Estratégia:** o SQL v2.0 continua a ser a referência canónica; cada
secção vira uma migration. Entreguei duas como padrão —
`CriarGeografia` (tabelas simples com UNIQUE e FK) e `CriarProgressoesFase`
(múltiplas FK com políticas `ON DELETE` distintas e UNIQUE de negócio) —
e o documento `ORDEM_MIGRATIONS.md` com a **sequência completa das ~20
migrations** por dependência de FK, mais a ordem dos seeders.

Dois pontos práticos documentados: o Shield traz as suas próprias
migrations (`php spark shield:setup`, corre antes das que referenciam
`users`); e views/triggers não têm suporte no Forge — a última migration
(9000) executa esses blocos do SQL v2.0 via `$this->db->query()`.

O `ProvinciasSeeder` já traz as **21 províncias** com os códigos de 3
letras usados no número de inscrição (`ANO-COD-SEQ`), e é idempotente
(`ignore(true)`) para poder ser reexecutado.

## 4. Fluxo ponta-a-ponta: inscrição pública

O `InscricaoController` público é a prova de que as camadas encaixam. Um
único fluxo exercita: **datas** (`edicaoAtivaParaInscricao` + prazo no
service), **validação centralizada** (regra nomeada `inscricaoPublica` +
`RegrasAngola`), **RN-01 por construção** (o formulário não envia
`provincia_id` — deriva da escola via dropdowns dependentes província →
município → escola por AJAX), **transação + número com lock + notificação**
(no `InscricaoService` da Fase 6), e **comprovativo com ID cifrado + TTL**
(o utilizador é redirecionado para `inscricao/sucesso/{token}`, nunca para
um ID cru). As views usam exclusivamente os componentes da Fase 8
(`campo`, `badge_estado`, fichas de letras no comprovativo).

Sequência completa:

```
GET  inscricao            → formulário (dropdowns dependentes por AJAX)
POST inscricao            → validate('inscricaoPublica')
                          → InscricaoService::inscrever() [transação]
                          → cifra id → redirect
GET  inscricao/sucesso/{token}  → comprovativo (nº + estado)
       ↓ (e-mail ao encarregado, fila SMS)
GET  inscricao/estado/{token}   → acompanhamento (link do e-mail)
```

## 5. Configuração de ambiente

O `env.referencia` reúne tudo num sítio: `appTimezone=UTC` (inegociável —
a estratégia de datas depende disso), as **duas** chaves de encriptação
distintas (geral + `urlcrypt.chave`), SMTP, e o bloco pro2sms com
`ativo=false` por omissão (desenvolvimento usa o `NuloProvider`).

## 6. Instalação de raiz (resumo)

```bash
composer install
cp env.referencia .env           # e preencher
php spark key:generate           # encryption.key
php spark key:generate --show    # copiar para urlcrypt.chave
composer require codeigniter4/shield ezyang/htmlpurifier
php spark shield:setup
php spark migrate
php spark db:seed InicialSeeder
# cron:
#  * * * * * php spark notificacoes:processar
#  * * * * * php spark cms:publicar-agendados
```

## 7. O que a Fase 9 deliberadamente NÃO inclui

Os controllers/models ainda não escritos (Eventos, Palavras, Media,
Utilizadores...) seguem **exatamente** os padrões já entregues: controller
fino → service → model com `noEscopo()`, views com os componentes da
Fase 8. Estão listados na Fase 10 com prioridades e estimativas — o
objetivo desta fase era a fundação executável e um vertical slice completo
(inscrição), não repetir o mesmo padrão vinte vezes.
