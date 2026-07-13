# FASE 3 — ARQUITETURA DO PROJETO EM CODEIGNITER 4
## Concurso Nacional de Soletração — Angola

> Versão 1.0 · Complementa a Fase 2 (banco de dados v2.0).
> Todos os exemplos de código seguem PSR-12 com comentários em português.

---

## 1. Princípios e visão geral

A arquitetura segue **MVC estendido com camada de serviços**:

```
Request → Rotas → Filtros → Controller (fino) → Service (regras de negócio)
                                              → Model/Entity (persistência)
                                              → View / JSON (apresentação)
```

Regras de ouro adotadas em todo o projeto:

1. **Controllers finos** — recebem a request, validam autorização de alto nível, delegam ao service e escolhem a resposta. Nunca contêm regras de negócio nem queries.
2. **Services gordos e reutilizáveis** — toda a regra de negócio (RN-01 a RN-10 da Fase 1) vive em services injetáveis, testáveis e partilhados entre área pública, área administrativa, comandos CLI (spark) e futura API.
3. **Models só persistem** — queries, escopos de pesquisa (`porProvincia()`, `publicadas()`) e callbacks de auditoria. Nada de lógica de domínio.
4. **Entities para representação** — conversão automática de datas (UTC ↔ Africa/Luanda), casts e métodos derivados (ex.: `Candidato::idadeEm($data)`).
5. **Uma única fonte de verdade por conceito transversal** — datas (`DataHoraService`), URLs cifradas (`UrlCryptService`), notificações (`Notificador`), auditoria (trait `Auditavel`).

### 1.1 Por que NÃO usar módulos HMVC externos

Avaliei duas alternativas: (a) pacotes de módulos HMVC de terceiros; (b) organização por **namespaces de domínio dentro de `app/`**. Recomendo a opção (b): é 100 % idiomática do CI4, compatível com Shield, `spark`, autoload PSR-4 e testes, sem dependências extra nem "magia" de descoberta de rotas. A separação por domínio é obtida com subpastas consistentes (`Services/Concurso`, `Controllers/Admin/Cms`, ...), o que dá os mesmos benefícios de coesão com custo zero.

---

## 2. Estrutura de pastas

```
app/
├── Commands/                        # Comandos CLI (spark)
│   ├── ProcessarFilaNotificacoes.php    # php spark notificacoes:processar
│   ├── PublicarConteudoAgendado.php     # php spark cms:publicar-agendados
│   └── FecharInscricoesExpiradas.php    # php spark concurso:fechar-inscricoes
│
├── Config/
│   ├── Routes.php                   # Apenas include dos ficheiros de rotas por área
│   ├── Soletracao.php               # Parâmetros de domínio do concurso
│   ├── Notificacoes.php             # Canais, retries, remetentes
│   ├── Pro2Sms.php                  # Credenciais/endpoints (lê do .env)
│   ├── UrlCrypt.php                 # Chave, TTL padrão dos tokens de URL
│   ├── AuthGroups.php               # Shield: grupos e matriz de permissões
│   └── Services.php                 # Registo dos services customizados
│
├── Controllers/
│   ├── BaseController.php
│   ├── Publico/                     # Portal público
│   │   ├── HomeController.php
│   │   ├── NoticiasController.php
│   │   ├── PaginasController.php
│   │   ├── ResultadosController.php
│   │   ├── InscricaoPublicaController.php
│   │   └── NewsletterController.php
│   ├── Admin/
│   │   ├── AdminBaseController.php  # Layout admin + escopo do utilizador
│   │   ├── DashboardController.php
│   │   ├── Concurso/                # Edições, fases, eventos, rounds, júri...
│   │   ├── Inscricoes/              # Candidatos, encarregados, validação
│   │   ├── Palavras/                # Banco de palavras + categorias
│   │   ├── Cms/                     # Notícias, páginas, media, menus, comentários
│   │   ├── Notificacoes/            # Templates, fila, logs e-mail/SMS
│   │   ├── Capacitacoes/
│   │   ├── Geografia/               # Províncias, municípios, escolas
│   │   └── Sistema/                 # Utilizadores, configurações, auditoria
│   └── Api/                         # Reservado (v2): endpoints JSON p/ palco ao vivo
│
├── Database/
│   ├── Migrations/                  # Uma migration por secção do SQL da Fase 2
│   └── Seeds/                       # ProvinciasSeeder, TemplatesNotificacaoSeeder...
│
├── Entities/
│   ├── Candidato.php                # idadeEm(), nomeExibicao()
│   ├── Inscricao.php
│   ├── Noticia.php                  # estaPublicada(), urlPublica()
│   ├── Palavra.php
│   └── ...
│
├── Filters/
│   ├── AuditoriaFilter.php          # Regista ações de escrita
│   ├── EscopoProvincialFilter.php   # Injeta e valida o escopo territorial
│   ├── ThrottleFilter.php           # Rate limit em rotas sensíveis
│   └── ManutencaoFilter.php
│
├── Helpers/
│   ├── data_helper.php              # data_exibir(), data_para_bd(), idade()
│   ├── texto_helper.php             # slug_pt(), excerto(), tempo_leitura()
│   ├── url_crypt_helper.php         # id_cifrar(), id_decifrar(), rota_segura()
│   └── formato_helper.php           # moeda_aoa(), telefone_ao(), badge_status()
│
├── Language/
│   ├── pt-AO/                       # Idioma base (default)
│   │   ├── Concurso.php
│   │   ├── Cms.php
│   │   ├── Notificacoes.php
│   │   ├── Validacao.php
│   │   └── Geral.php
│   └── en/                          # Preparado para i18n futura (vazio por agora)
│
├── Libraries/
│   └── Sms/
│       ├── SmsProviderInterface.php
│       ├── SmsMensagem.php          # Value object
│       ├── SmsResultado.php         # Value object
│       └── Providers/
│           ├── Pro2SmsProvider.php  # Integração https://pro2sms.ao
│           └── NuloProvider.php     # Para desenvolvimento/testes (não envia)
│
├── Models/
│   ├── CandidatoModel.php
│   ├── InscricaoModel.php
│   ├── ... (um model por tabela de negócio)
│
├── Services/                        # NÚCLEO DA APLICAÇÃO
│   ├── Comum/
│   │   ├── DataHoraService.php      # UTC ↔ Africa/Luanda, prazos, idade
│   │   ├── AuditoriaService.php
│   │   ├── ConfiguracaoService.php  # Lê/escreve tabela configuracoes (com cache)
│   │   ├── UploadService.php        # Uploads seguros (whitelist, rename, hash)
│   │   └── EscopoService.php        # Resolve o território do utilizador
│   ├── Seguranca/
│   │   ├── UrlCryptService.php      # Cifra/assina parâmetros de URL
│   │   └── AutorizacaoService.php   # Regras finas além do Shield
│   ├── Concurso/
│   │   ├── InscricaoService.php     # RN-01, RN-02, RN-05, nº de inscrição
│   │   ├── ProgressaoService.php    # RN-04: qualificação entre fases
│   │   ├── EventoService.php
│   │   ├── RoundService.php
│   │   ├── TentativaService.php     # RN-07: registo/avaliação/apelação
│   │   ├── ClassificacaoService.php # Posições, homologação, desempates
│   │   └── PalavraService.php       # Validação, pool, sorteio por dificuldade
│   ├── Cms/
│   │   ├── NoticiaService.php       # Fluxo editorial, agendamento, revisões
│   │   ├── PaginaService.php
│   │   ├── MediaService.php
│   │   ├── MenuService.php
│   │   └── ComentarioService.php    # Moderação, anti-spam
│   └── Notificacoes/
│       ├── Notificador.php          # Fachada única: notificar(evento, destinatário, dados)
│       ├── TemplateRenderer.php     # Substitui {{placeholders}}
│       ├── FilaService.php          # Enfileira, backoff, retries
│       └── Canais/
│           ├── CanalInterface.php
│           ├── CanalSistema.php
│           ├── CanalEmail.php       # CI4 Email + logs_email
│           └── CanalSms.php         # SmsProviderInterface + logs_sms
│
├── Traits/
│   ├── Auditavel.php                # Callbacks de model → auditoria_logs
│   ├── ComUuid.php                  # Gera uuid v4 no insert
│   └── RespostasPadrao.php          # Respostas JSON/redirect consistentes
│
└── Views/
    ├── layouts/
    │   ├── publico.php              # Portal institucional
    │   ├── admin.php                # Backoffice
    │   └── auth.php                 # Login/registo (Shield personalizado)
    ├── components/                  # Parciais reutilizáveis (Fase 8)
    │   ├── formulario/  tabela/  cartao/  badge/  alerta/  paginacao/
    ├── publico/
    │   ├── home/  noticias/  paginas/  resultados/  inscricao/
    └── admin/
        ├── dashboard/  concurso/  inscricoes/  palavras/  cms/
        ├── notificacoes/  geografia/  sistema/
```

Rotas divididas por área para manter o `Routes.php` legível:

```php
// app/Config/Routes.php
require APPPATH . 'Config/Rotas/publico.php';
require APPPATH . 'Config/Rotas/admin.php';
require APPPATH . 'Config/Rotas/auth.php';   // service('auth')->routes($routes) + overrides
```

---

## 3. Integração com CodeIgniter Shield

### 3.1 Setup

```bash
composer require codeigniter4/shield
php spark shield:setup          # migrations de auth (já refletidas na Fase 2)
```

- As tabelas `users`, `auth_*` são geridas pelo Shield; `perfis_utilizador` estende-as 1:1.
- Entidade de utilizador própria (`app/Entities/Utilizador.php` estendendo `Shield\Entities\User`) com acesso lazy ao perfil: `$user->perfil()->nome_completo`.
- Views de autenticação do Shield são **sobrepostas** em `app/Views/auth/` (tema próprio, textos pt-AO).

### 3.2 Grupos e permissões (matriz)

Convenção de nomes de permissão: `dominio.recurso.acao` — legível, filtrável por prefixo e estável.

```php
// app/Config/AuthGroups.php (excerto)
public array $groups = [
    'superadmin'       => ['title' => 'Superadministrador'],
    'coord_nacional'   => ['title' => 'Coordenador Nacional'],
    'coord_provincial' => ['title' => 'Coordenador Provincial'],
    'coord_escolar'    => ['title' => 'Coordenador Escolar'],
    'professor'        => ['title' => 'Professor Responsável'],
    'jurado'           => ['title' => 'Jurado'],
    'pronunciador'     => ['title' => 'Pronunciador'],
    'encarregado'      => ['title' => 'Encarregado de Educação'],
    'candidato'        => ['title' => 'Candidato'],
    'editor_noticias'  => ['title' => 'Editor de Notícias'],
    'jornalista'       => ['title' => 'Jornalista'],
];

public array $permissions = [
    // Concurso
    'concurso.edicoes.gerir'        => 'Criar e configurar edições, fases e categorias',
    'concurso.eventos.gerir'        => 'Criar e conduzir eventos e rounds',
    'concurso.resultados.homologar' => 'Homologar e publicar resultados',
    'concurso.juri.avaliar'         => 'Avaliar tentativas e decidir apelações',
    // Inscrições
    'inscricoes.criar'              => 'Inscrever candidatos',
    'inscricoes.validar'            => 'Validar/rejeitar inscrições',
    // Palavras
    'palavras.gerir'                => 'Criar e editar palavras',
    'palavras.validar'              => 'Validar palavras para uso em concurso',
    // CMS
    'cms.conteudo.criar'            => 'Criar rascunhos de notícias/páginas',
    'cms.conteudo.publicar'         => 'Aprovar, agendar e publicar',
    'cms.comentarios.moderar'       => 'Moderar comentários',
    'cms.media.gerir'               => 'Gerir biblioteca de media',
    // Sistema
    'sistema.utilizadores.gerir'    => 'Gerir utilizadores e grupos',
    'sistema.configuracoes.gerir'   => 'Alterar configurações globais',
    'sistema.auditoria.ver'         => 'Consultar logs de auditoria',
];

public array $matrix = [
    'superadmin'       => ['*'],
    'coord_nacional'   => ['concurso.*', 'inscricoes.*', 'palavras.*',
                           'cms.conteudo.publicar', 'sistema.auditoria.ver'],
    'coord_provincial' => ['concurso.eventos.gerir', 'inscricoes.*'],
    'coord_escolar'    => ['inscricoes.criar'],
    'professor'        => ['inscricoes.criar'],
    'jurado'           => ['concurso.juri.avaliar'],
    'pronunciador'     => ['concurso.juri.avaliar'],
    'editor_noticias'  => ['cms.*'],
    'jornalista'       => ['cms.conteudo.criar', 'cms.media.gerir'],
    'encarregado'      => [],   // acesso só ao próprio portal do encarregado
    'candidato'        => [],
];
```

### 3.3 Autorização em duas camadas (decisão importante)

O Shield responde **"pode fazer esta ação?"** (permissão). Mas o domínio exige também **"pode fazê-la SOBRE ESTE registo?"** (escopo territorial): um coordenador provincial de Benguela não pode validar inscrições do Huambo, mesmo tendo `inscricoes.validar`.

A solução é o par `EscopoService` + `AutorizacaoService`:

```php
// app/Services/Comum/EscopoService.php (conceito)
final class EscopoService
{
    /**
     * Resolve o escopo territorial do utilizador autenticado a partir
     * de coordenadores_atribuicao. Cacheado por request.
     * Retorna: ['nivel' => 'provincial', 'provincias' => [4], 'escolas' => []]
     */
    public function do(int $userId): Escopo { ... }
}

// app/Services/Seguranca/AutorizacaoService.php (conceito)
final class AutorizacaoService
{
    /** Lança ExcecaoAutorizacao se o registo estiver fora do escopo. */
    public function exigirEscopo(Escopo $escopo, HasProvincia $registo): void { ... }
}
```

Os models expõem escopos de query para que as listagens já venham filtradas:

```php
// Em InscricaoModel
public function noEscopo(Escopo $escopo): static
{
    if ($escopo->nivel === 'provincial') {
        $this->whereIn('inscricoes.provincia_id', $escopo->provincias);
    } elseif ($escopo->nivel === 'escolar') {
        $this->whereIn('inscricoes.escola_id', $escopo->escolas);
    }
    return $this; // nível nacional não filtra
}
```

---

## 4. Filtros (pipeline de request)

```php
// app/Config/Filters.php (excerto)
public array $aliases = [
    'session'    => \CodeIgniter\Shield\Filters\SessionAuth::class,
    'group'      => \CodeIgniter\Shield\Filters\GroupFilter::class,
    'permission' => \CodeIgniter\Shield\Filters\PermissionFilter::class,
    'escopo'     => \App\Filters\EscopoProvincialFilter::class,
    'auditoria'  => \App\Filters\AuditoriaFilter::class,
    'throttle'   => \App\Filters\ThrottleFilter::class,
];

public array $globals = [
    'before' => ['csrf' => ['except' => ['api/*']]],  // CSRF em toda a web
    'after'  => [],
];
```

Aplicação nas rotas administrativas:

```php
// app/Config/Rotas/admin.php
$routes->group('admin', [
    'namespace' => 'App\Controllers\Admin',
    'filter'    => ['session', 'escopo', 'auditoria'],
], static function ($routes) {

    $routes->get('/', 'DashboardController::index');

    // Validação de inscrições: exige permissão específica
    $routes->group('inscricoes', ['filter' => 'permission:inscricoes.validar'],
        static function ($routes) {
            $routes->get('/',            'Inscricoes\InscricoesController::index');
            $routes->get('ver/(:segment)',   'Inscricoes\InscricoesController::ver/$1');    // $1 = id cifrado
            $routes->post('validar/(:segment)', 'Inscricoes\InscricoesController::validar/$1');
        });

    // CMS
    $routes->group('cms/noticias', ['filter' => 'permission:cms.conteudo.criar'],
        static function ($routes) { /* CRUD (Fase 9) */ });
});
```

Papel de cada filtro customizado:

| Filtro | Momento | Função |
|---|---|---|
| `EscopoProvincialFilter` | before | Resolve o `Escopo` do utilizador e injeta-o em `Services::escopoAtual()`; bloqueia contas sem atribuição ativa |
| `AuditoriaFilter` | after | Para métodos POST/PUT/DELETE bem-sucedidos, delega ao `AuditoriaService` (rota, entidade, user, IP) |
| `ThrottleFilter` | before | Rate limit (Throttler nativo) em login, inscrição pública, comentários e newsletter — ex.: 10 req/min/IP |
| `ManutencaoFilter` | before | Modo manutenção via `configuracoes`, com bypass para superadmin |

---

## 5. Camada de segurança

### 5.1 UrlCryptService — parâmetros cifrados/assinados na URL

Requisito especial do projeto. Estratégia: **cifrar + autenticar** o parâmetro com o `Encryption` nativo do CI4 (AES-256-CTR + HMAC-SHA512, chave própria em `.env`), codificado em **base64url** (URL-safe), com **contexto** e **TTL opcional** embutidos.

```php
// app/Services/Seguranca/UrlCryptService.php (interface pública)
final class UrlCryptService
{
    /**
     * Cifra um ID para uso em URL.
     * $contexto liga o token ao recurso (ex.: 'inscricao') — um token de
     * inscrição não pode ser reutilizado numa rota de candidato.
     * $ttl em segundos (null = não expira).
     */
    public function cifrar(int|string $id, string $contexto, ?int $ttl = null): string;

    /**
     * Decifra e valida. Lança TokenInvalidoException se o token for
     * adulterado, de outro contexto ou expirado.
     */
    public function decifrar(string $token, string $contexto): int|string;
}
```

Helper de conveniência para uso nas views e controllers:

```php
// app/Helpers/url_crypt_helper.php
id_cifrar($inscricao->id, 'inscricao');                       // → token URL-safe
id_decifrar($token, 'inscricao');                              // → int|excepção
rota_segura('admin/inscricoes/ver', $inscricao->id, 'inscricao'); // URL completa
```

Tratamento de links inválidos: `TokenInvalidoException` é apanhada centralmente
(`app/Config/Exceptions` handler) → resposta 404 amigável ("Este link é inválido ou
expirou") + registo em auditoria com IP. **Nunca** 500, nunca detalhe técnico.

Onde aplicar (política): rotas administrativas de candidatos, inscrições,
encarregados e utilizadores; links enviados por e-mail/SMS (com TTL, ex.: 72 h).
Onde NÃO aplicar: conteúdos públicos por natureza (notícias, páginas, resultados),
que usam `slug`/`uuid` — cifrar aí só prejudicaria SEO e partilha.

### 5.2 Restantes controlos

- **Validação centralizada**: regras nomeadas em `app/Validation/` (ex.: `RegrasCandidato`), reutilizadas por controllers e services; regras custom (`telefone_ao`, `classe_valida`, `data_nao_futura`) registadas em `Config/Validation.php`; mensagens em `Language/pt-AO/Validacao.php`.
- **CSRF** global (cookie-based, regenerate on) exceto API.
- **Escape de saída**: `esc()` obrigatório em todas as views; conteúdo rico do CMS passa por sanitização HTML (whitelist de tags) no `NoticiaService`, nunca na view.
- **Uploads seguros** (`UploadService`): whitelist de MIME real (finfo), renomeação aleatória, armazenamento fora de `public/` com entrega via controller para documentos de inscrição; imagens de media library re-processadas (strip EXIF, redimensionamento).
- **Auditoria**: trait `Auditavel` nos models regista before/after (JSON) em `auditoria_logs` via callbacks `afterInsert/afterUpdate/afterDelete`.

---

## 6. Camada de notificações

Arquitetura em três níveis, alinhada com as tabelas da Fase 2:

```
Evento de domínio (ex.: inscrição validada)
        │
        ▼
Notificador::notificar('inscricao_validada', $destinatarios, $dados)
        │  1. resolve os templates ativos do evento (por canal)
        │  2. TemplateRenderer substitui {{placeholders}}
        │  3. canal 'sistema' → grava direto em notificacoes
        │  4. canais 'email'/'sms' → FilaService::enfileirar(...)
        ▼
php spark notificacoes:processar   (cron a cada minuto)
        │  lê notificacoes_fila (status, proxima_tentativa_em, prioridade)
        │  CanalEmail → CI4 Email → logs_email
        │  CanalSms   → SmsProviderInterface → logs_sms
        │  sucesso → status 'enviada' | falha → retry com backoff (1m, 5m, 25m)
```

Decisões:

1. **Envio sempre assíncrono** para e-mail/SMS. O request do utilizador apenas enfileira — a latência da pro2sms nunca bloqueia a UI.
2. **Provider substituível**: `CanalSms` depende só de `SmsProviderInterface`; trocar de provedor é registar outra classe em `Config/Services.php`. Em desenvolvimento usa-se `NuloProvider` (loga sem enviar).
3. **Configuração por `.env`** (excerto):

```dotenv
notificacoes.email.remetente = nao-responder@soletracao.ao
pro2sms.baseUrl   = https://pro2sms.ao/api
pro2sms.apiKey    = ********
pro2sms.senderId  = SOLETRACAO
pro2sms.timeout   = 10
pro2sms.ativo     = true
```

4. **Contrato do provedor**:

```php
interface SmsProviderInterface
{
    /** Envia um SMS e devolve o resultado normalizado (nunca lança para falha de rede — devolve SmsResultado::falha()). */
    public function enviar(SmsMensagem $mensagem): SmsResultado;
    public function nome(): string;   // 'pro2sms'
}
```

`Pro2SmsProvider` usa o `CURLRequest` do CI4 com timeout, valida o número para E.164 (+244...), calcula segmentos GSM-7 e devolve `SmsResultado` com `provider_message_id`, custo e resposta bruta (gravados em `logs_sms`). Código completo na Fase 9.

---

## 7. Camada CMS

Services do CMS encapsulam o fluxo editorial (a view/controller nunca muda `status` diretamente):

- `NoticiaService::submeterParaRevisao()`, `::aprovar()`, `::agendar(DateTime $quando)`, `::publicar()`, `::arquivar()` — cada transição valida a permissão (`cms.conteudo.publicar`) e o estado de origem (máquina de estados: rascunho → revisão → agendada|publicada → arquivada), regista revisão e dispara `Notificador` quando relevante.
- Agendamento concretizado pelo comando `cms:publicar-agendados` (cron), que publica notícias com `status = 'agendada'` e `data_agendada <= agora`.
- `MediaService` é o único ponto de upload do CMS (usa o `UploadService` comum) e devolve registos de `media_biblioteca` reutilizáveis por notícias, páginas e patrocinadores.
- Slugs: `slug_pt()` (helper) com transliteração completa; `SlugService` garante unicidade com sufixo `-2`, `-3`...
- SEO: entidade `Noticia` calcula fallbacks (`meta_titulo ?? titulo`, `og_imagem ?? imagem destacada`), centralizando a lógica que as views consomem.

## 8. Camada do concurso

Os services espelham o ciclo de vida competitivo e são os únicos guardiões das regras RN:

| Service | Responsabilidade central |
|---|---|
| `InscricaoService` | Cria candidato+encarregado+inscrição numa **transação**; valida província (RN-01), unicidade por edição (RN-02), classe/idade vs. categoria (RN-03), prazos (via `DataHoraService`); gera `numero_inscricao` `ANO-COD-SEQ` com `SELECT ... FOR UPDATE`; dispara notificações |
| `ProgressaoService` | Calcula qualificados de um evento (posição ≤ vagas da fase), grava `progressoes_fase`, cria as `participacoes` na fase seguinte; suporta repescagem/substituição com homologação |
| `EventoService` / `RoundService` | Agenda eventos, atribui júri, abre/fecha rounds, valida pool de palavras suficiente para o round |
| `TentativaService` | Entrega a próxima palavra não usada do pool (por dificuldade do round), regista a tentativa, avalia (comparação com `palavra_normalizada` + decisão do juiz), processa apelações |
| `ClassificacaoService` | Calcula posições ao fecho do evento (sobreviventes > rounds sobrevividos > pontos > tempo, conforme regulamento), exige homologação antes de tornar público |
| `PalavraService` | CRUD + validação pedagógica, importação em lote, estatísticas (taxa de acerto) |

## 9. Registo de services e datas

Todos os services são singletons registados em `Config/Services.php`, o que dá injeção limpa e substituição fácil em testes:

```php
// app/Config/Services.php (excerto)
public static function notificador(bool $getShared = true): Notificador
{
    if ($getShared) return static::getSharedInstance('notificador');
    return new Notificador(static::filaNotificacoes(), static::templateRenderer());
}

public static function smsProvider(bool $getShared = true): SmsProviderInterface
{
    // Troca de provedor = alterar apenas esta linha (ou ler de configuracoes)
    return new Pro2SmsProvider(config(Pro2Sms::class), static::curlrequest());
}

public static function urlCrypt(bool $getShared = true): UrlCryptService { ... }
public static function dataHora(bool $getShared = true): DataHoraService { ... }
public static function escopoAtual(): Escopo { ... }   // definido pelo filtro
```

**Estratégia de datas (transversal, requisito especial):**

- `app.appTimezone = UTC` em `Config/App.php` — o CI4, os models (`created_at`...) e o MySQL trabalham sempre em UTC.
- `DataHoraService` é o único conversor: `paraExibicao(Time $t): string` (Africa/Luanda, formatos pt-AO), `deFormulario(string $input): Time` (interpreta input local → UTC), `idadeEm(Time $nascimento, Time $referencia): int`, `dentroDoPrazo(Time $inicio, Time $fim): bool`.
- Entities fazem cast automático (`'data_nascimento' => 'datetime'`) e as views usam apenas o helper `data_exibir($valor, 'curta'|'longa'|'hora')` — proibido `date()`/`format()` solto nas views.

---

## 10. Resumo das decisões e alternativas

| Decisão | Alternativa considerada | Porquê a escolha |
|---|---|---|
| Namespaces de domínio em `app/` | Módulos HMVC de terceiros | Zero dependências, idiomático, compatível com Shield/spark |
| Autorização em 2 camadas (permissão + escopo) | Só grupos do Shield | Grupos não expressam território; escopo evita fugas entre províncias |
| Fila própria em MySQL + spark/cron | Redis/RabbitMQ | Infra simples (hosting típico em Angola), volume moderado; interface `FilaService` permite migrar depois |
| Token cifrado com contexto+TTL | Apenas hashids/uuid | Hashids são reversíveis por força bruta; contexto impede troca de token entre rotas; uuid mantém-se para links públicos estáveis |
| Sessão (Shield) na web; tokens só na futura API | JWT em tudo | Sessão + CSRF é mais simples e seguro para uma app server-rendered |
| Regras de negócio em services transacionais | Lógica nos models/controllers | Reutilização entre web, CLI e API; testabilidade; transações explícitas |
