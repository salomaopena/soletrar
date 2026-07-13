# FASE 4 — SEGURANÇA E REUTILIZAÇÃO
## Concurso Nacional de Soletração — Angola

> Acompanha o pacote `fase4_seguranca_codigo.zip`, que contém os ficheiros PHP
> prontos a colocar no projeto (caminhos já corretos dentro de `app/`).

---

## 1. Mapa do que foi entregue

| Requisito da fase | Implementação | Ficheiro(s) |
|---|---|---|
| Helper/service de encriptação de parâmetros | `UrlCryptService` + helper `url_crypt_helper` + config dedicada | `Services/Seguranca/UrlCryptService.php`, `Helpers/url_crypt_helper.php`, `Config/UrlCrypt.php` |
| Tratamento de links inválidos | `TokenInvalidoException` (→ 404 amigável + log com IP) | `Exceptions/TokenInvalidoException.php` |
| Políticas de autorização | 2 camadas: Shield (permissão) + escopo territorial | `Services/Comum/Escopo.php`, `EscopoService.php`, `Services/Seguranca/AutorizacaoService.php`, `Exceptions/AutorizacaoException.php` |
| Validação centralizada | Regras nomeadas + regras do domínio angolano | `Validation/RegrasAngola.php`, excertos de `Config/Validation.php` |
| Logs de auditoria | Service único + trait para models + filtro HTTP | `Services/Comum/AuditoriaService.php`, `Traits/Auditavel.php`, `Filters/AuditoriaFilter.php` |
| Uploads seguros | Perfis com whitelist, MIME real, re-encode, área privada | `Services/Comum/UploadService.php` |
| Rate limit | Filtro parametrizável por rota | `Filters/ThrottleFilter.php` |
| Exemplos práticos em CRUD | Controller de inscrições com tudo integrado | `Controllers/Admin/Inscricoes/InscricoesController.php` |
| Proteção CSRF e escape de saída | Políticas documentadas abaixo (config nativa) | `Config/EXCERTOS_CONFIG.php` |

---

## 2. Encriptação de parâmetros de URL — como usar

### 2.1 Setup (uma vez)

```bash
# Gerar a chave dedicada e colocar no .env
php spark key:generate --show
```

```dotenv
# .env
urlcrypt.chave = hex2bin:5f2b...   # chave DIFERENTE da encryption.key geral
```

Registar o helper globalmente em `app/Config/Autoload.php`:

```php
public $helpers = ['url_crypt', 'data', 'formato'];
```

### 2.2 Nas views (gerar links)

```php
<a href="<?= rota_segura('admin/inscricoes/ver', $inscricao->id, 'inscricao') ?>">
    Ver inscrição
</a>
```

Resultado: `https://.../admin/inscricoes/ver/kJ8xW3q...` — opaco, autenticado,
inutilizável noutra rota.

### 2.3 Nos controllers (receber)

```php
public function ver(string $token)
{
    $id = (int) id_decifrar($token, 'inscricao');   // 404 automático se inválido
    ...
}
```

### 2.4 Em links enviados por e-mail/SMS (com expiração)

```php
$token = service('urlCrypt')->cifrarLinkExterno($inscricao->id, 'comprovativo');
$url   = site_url("inscricao/comprovativo/{$token}");   // expira em 72 h
```

### 2.5 Política de aplicação (onde cifrar)

| Recurso | Estratégia de URL | Porquê |
|---|---|---|
| Candidatos, inscrições, encarregados, utilizadores (admin) | **ID cifrado** com contexto | Dados pessoais de menores; enumeração de IDs é inaceitável |
| Links em e-mail/SMS (comprovativos, convocatórias) | ID cifrado **com TTL 72 h** | O link pode ser reencaminhado; tem de morrer |
| Confirmação de newsletter, reposição de senha | **Token opaco** (`gerarTokenOpaco()`) guardado em BD | Não há ID a proteger — o token É a credencial |
| Notícias, páginas, resultados públicos | **Slug/uuid**, sem cifra | Conteúdo público: cifrar mataria SEO e partilha |

---

## 3. Políticas de autorização

### 3.1 As duas perguntas, sempre por esta ordem

1. **"Pode executar esta ação?"** → filtro `permission:` do Shield na rota
   (falha = 401/redirect antes de o controller correr).
2. **"Pode executá-la sobre ESTE registo?"** → `service('autorizacao')->exigirEscopo($escopo, $registo)`
   no controller/service (falha = `AutorizacaoException` → 403 **+ registo em auditoria**).

E nas **listagens**, a pergunta 2 é respondida na query: `->noEscopo($escopo)`
nos models. Regra de revisão de código: *nenhuma listagem administrativa sem
`noEscopo()`; nenhum detalhe/escrita sem `exigirEscopo()`.*

### 3.2 Comportamento seguro por omissão

Utilizador com grupo de coordenação mas **sem atribuição ativa** em
`coordenadores_atribuicao` recebe escopo vazio → não vê nada. É preferível um
chamado ao suporte ("não vejo as minhas inscrições") a uma fuga de dados.

---

## 4. Validação centralizada

- Regras **nomeadas** em `Config/Validation.php` (`$criarCandidato`,
  `$rejeitarInscricao`...) — o mesmo conjunto serve o formulário público, o
  backoffice e a importação CLI.
- Regras do **domínio angolano** em `Validation/RegrasAngola.php`:
  `telefone_ao`, `bi_angola`, `classe_valida`, `data_nao_futura`,
  `idade_entre[min,max]`.
- Mensagens exclusivamente em `Language/pt-AO/Validation.php` — nunca
  hard-coded.
- Services re-validam invariantes críticas (RN-01, prazos) mesmo que o
  controller já tenha validado: a validação de formulário protege a UX; a do
  service protege os dados.

## 5. Proteção CSRF

Configuração adotada (`Config/Security.php` + filtro global):

```php
public $csrfProtection = 'session';   // mais robusto que cookie em multi-tab
public $tokenRandomize = true;        // token diferente por request (anti-BREACH)
public $regenerate     = true;
public $redirect       = false;       // lança SecurityException → página de erro
```

- Filtro `csrf` **global** exceto `api/*` (a futura API usará tokens Shield).
- Todos os formulários usam `<?= csrf_field() ?>`; pedidos AJAX enviam o header
  `X-CSRF-TOKEN` (o layout admin expõe `csrf_hash()` num meta tag).

## 6. Escape de saída (política de views)

1. `esc()` em **toda** a saída de dados nas views: `esc($candidato->nome_completo)`.
   Contexto explícito quando não é HTML: `esc($url, 'attr')`, `esc($js, 'js')`.
2. **Exceção única**: conteúdo rico do CMS (`$noticia->conteudo`), que é
   sanitizado **na entrada** pelo `NoticiaService` (whitelist de tags:
   `p, br, h2–h4, ul, ol, li, a[href], img[src|alt], blockquote, strong, em, figure, figcaption, table…`)
   e por isso impresso sem `esc()` — com comentário obrigatório na view:
   `<?php /* sanitizado no NoticiaService */ ?>`.
3. Proibido `echo $this->request->getGet(...)` direto — dados de request nunca
   vão à saída sem passar por `esc()`.

## 7. Auditoria — três níveis complementares

| Nível | Mecanismo | O que responde |
|---|---|---|
| Dados | Trait `Auditavel` nos models (before/after em JSON) | "O que mudou nesta tabela, de que valor para que valor?" |
| Ação | `AuditoriaFilter` (escritas HTTP 2xx/3xx na área admin) | "Que operações este utilizador executou, em que rotas?" |
| Segurança | Registos explícitos: `acesso_negado_escopo`, tokens inválidos, rate limit | "Quem tentou o que não devia?" |

Salvaguardas: campos sensíveis (`password`, `secret`, `token`...) são
substituídos por `[REDIGIDO]` antes de gravar; a tabela `auditoria_logs` é
*append-only* por convenção (nenhum model com update/delete sobre ela) e
consultável apenas com `sistema.auditoria.ver`.

## 8. Uploads seguros — perfis

| Perfil | MIME aceites | Máx. | Localização | Re-encode |
|---|---|---|---|---|
| `imagem_media` | jpeg, png, webp, gif | 5 MB | `public/uploads/media/AAAA/MM` | Sim (remove EXIF/GPS e payloads) |
| `documento_inscricao` | pdf, jpeg, png | 8 MB | `writable/uploads_privados/...` (**fora de public/**) | Não |
| `audio_palavra` | mp3, ogg, wav | 4 MB | `public/uploads/palavras/...` | Não |

Documentos de inscrição (BI/cédula de menores!) são servidos por um controller
dedicado (`DocumentosController::servir($token)`) que exige sessão + permissão +
`exigirEscopo()` antes de fazer stream do ficheiro — nunca por URL direta.

## 9. Rate limits recomendados

| Rota | Limite | Justificação |
|---|---|---|
| Login | `throttle:5,1` | Trava força bruta sem penalizar erros honestos |
| Inscrição pública (POST) | `throttle:3,10` | Inscrever 1 candidato é raro; 3/10 min já é generoso |
| Comentários (POST) | `throttle:5,5` | Anti-spam básico antes da moderação |
| Newsletter (POST) | `throttle:3,10` | Evita bombardear terceiros com e-mails de confirmação |
| Pesquisa pública | `throttle:30,1` | Protege o FULLTEXT de scraping agressivo |
| Área admin | — | Sessão + permissões já limitam; throttle atrapalharia o júri ao vivo |

O balde do Throttler é isolado por **IP + rota**, para que esgotar o limite de
uma rota não bloqueie as restantes.

## 10. Checklist de integração no projeto

1. Copiar os ficheiros do zip para `app/` (estrutura já corresponde).
2. Fundir os excertos de `Config/EXCERTOS_CONFIG.php` em `Services.php`,
   `Validation.php` e `Filters.php` reais.
3. `.env`: definir `urlcrypt.chave`.
4. `Autoload.php`: registar o helper `url_crypt`.
5. Adicionar as chaves de linguagem usadas (`Geral.linkInvalido`,
   `Geral.foraDoEscopo`, `Geral.demasiadosPedidos`, `Geral.uploadInvalido`,
   `Geral.uploadDemasiadoGrande`, `Geral.uploadTipoNaoPermitido`,
   `Concurso.inscricaoValidada`, `Concurso.inscricaoRejeitada`) em
   `Language/pt-AO/`.
6. Nos models auditáveis, ativar o trait `Auditavel` e os quatro callbacks
   (instruções no docblock do trait).
7. Criar as views de erro `erros/429` e personalizar a 404/403.
