# FASE 7 — NOTIFICAÇÕES (sistema, e-mail e SMS via pro2sms.ao)
## Concurso Nacional de Soletração — Angola

> Acompanha `fase7_notificacoes_codigo.zip` (16 ficheiros PHP).
> Fecha a implementação da arquitetura desenhada na Fase 3 sobre as
> tabelas da v2.0 (`notificacoes`, `notificacoes_templates`,
> `notificacoes_fila`, `logs_email`, `logs_sms`).

---

## 1. Visão geral do pipeline

```
Domínio (InscricaoService, ClassificacaoService, NoticiaService...)
    │  service('notificador')->notificar('inscricao_validada', dest, dados)
    ▼
Notificador (fachada única)
    │ 1. templates ativos do evento  ── convenção: {evento}_{canal}
    │ 2. TemplateRenderer: {{placeholders}} → texto final
    ├─ canal sistema → escrita DIRETA em notificacoes (síncrono)
    └─ email / sms   → FilaService.enfileirar()  (assíncrono)
                            ▼
        php spark notificacoes:processar   (cron * * * * *)
            │ reclamarLote() — reclamação ATÓMICA (multi-worker safe)
            ├─ CanalEmail → SMTP (CI4 Email) ──► logs_email
            └─ CanalSms   → SmsProviderInterface ──► logs_sms
                              └─ Pro2SmsProvider (prod) | NuloProvider (dev)
            │ sucesso → enviada · falha → backoff 60s/300s/1500s → falhada
                            ▲
        POST api/sms/callback (webhook DLR) → logs_sms.status = entregue
```

## 2. Decisões de desenho e porquê

**Templates em BD com convenção `{evento}_{canal}`.** Desligar um tipo de
SMS é desativar o template no backoffice — sem deploy. Um evento sem
template ativo num canal simplesmente não envia nesse canal (com warning
no log, para diagnosticar esquecimentos).

**Corpo gravado na fila JÁ renderizado**, com o contexto em `dados_json`.
O log é fiel ao que foi efetivamente enviado, mesmo que o template mude
depois; e o retry não re-renderiza (dados que mudaram entretanto não
alteram uma mensagem já composta).

**Reclamação atómica do lote.** O worker marca até N mensagens como
`a_enviar` com uma etiqueta única (`UPDATE ... ORDER BY prioridade, id
LIMIT N`) e só depois as lê. Dois workers em paralelo nunca processam a
mesma mensagem — importante porque no dia de um evento nacional pode ser
preciso correr um segundo worker manualmente.

**Falha definitiva ≠ falha recuperável.** Telefone inválido (não
normalizável para +2449XXXXXXXX) é descartado com log `falhado` e SEM
retry — reagendar não conserta um número errado. Falha de rede/provedor
devolve `SmsResultado::falha()` e entra no ciclo de backoff. E o contrato
da interface é explícito: `enviar()` nunca lança por falha de
rede — exceção é bug, e o worker apanha-a sem parar a fila (uma mensagem
"envenenada" não bloqueia as restantes).

**Segmentação real de SMS.** `SmsMensagem` deteta se o texto cabe no
alfabeto GSM-7 (160/153 por parte) ou força UCS-2 (70/67) — é por isso
que os templates SMS semeados na Fase 2 evitam acentuação: `ã õ ç` não
estão no GSM básico e triplicariam o custo por mensagem. O nº de partes é
gravado em `logs_sms` para controlo de custos.

**Prioridade na fila.** 1–9 (menor = mais urgente). Resultados de eventos
saem com prioridade 3; o resto com 5. Numa fila cheia após uma final
provincial, as convocatórias e resultados furam a newsletter.

## 3. Integração pro2sms.ao

A estrutura está completa e isolada; os únicos pontos a afinar com a
documentação oficial/credenciais reais estão **marcados `[AJUSTAR]`** em
exatamente três sítios:

1. `Config/Pro2Sms.php` → `endpointEnvio` (caminho do endpoint);
2. `Pro2SmsProvider` → esquema de autenticação (Bearer vs api-key) e
   nomes dos campos do payload/resposta (`to/from/message`,
   `message_id/cost/error`);
3. `SmsCallbackController` → nomes dos campos do relatório de entrega.

`.env` completo do provedor:

```dotenv
pro2sms.baseUrl       = https://pro2sms.ao/api
pro2sms.apiKey        = ********
pro2sms.senderId      = SOLETRACAO
pro2sms.timeout       = 10
pro2sms.ativo         = true          # false → NuloProvider (dev/testes)
pro2sms.callbackToken = <aleatório longo>   # valida o webhook DLR
```

Substituir o provedor no futuro = nova classe que implemente
`SmsProviderInterface` + trocar uma linha em `Config/Services.php`.
Mais nada muda: canal, fila, logs e templates são agnósticos.

## 4. Falhas, retries e observabilidade

| Situação | Comportamento |
|---|---|
| SMTP/pro2sms indisponível | retry com backoff 60 s → 300 s → 1500 s; depois `falhada` |
| Telefone inválido | descartado de imediato; `logs_sms.status = falhado`, sem retry |
| Exceção inesperada num canal | log `critical`, mensagem falha, fila continua |
| Template sem placeholder fornecido | substitui por vazio + warning (nunca `{{x}}` cru num SMS) |
| DLR do provedor | webhook atualiza `logs_sms` para `entregue`/`falhado`/`expirado` |

Consultas de operação (backoffice, `Admin/Notificacoes`): fila por estado,
`falhadas` com erro e botão de re-enfileirar, `logs_sms` com custo somado
por dia/província, `logs_email` filtrável por destinatário.

## 5. Catálogo de eventos que disparam notificação

| Evento (código) | Disparado por | Canais típicos | Destinatário |
|---|---|---|---|
| `inscricao_recebida` | `InscricaoService::inscrever` | email | Encarregado |
| `inscricao_validada` | `InscricaoService::validar` | sms + email | Encarregado |
| `inscricao_rejeitada` | `InscricaoService::rejeitar` | sms + email | Encarregado |
| `evento_convocatoria` | Agendamento de evento | sms | Encarregados dos confirmados |
| `resultado_publicado` | `ClassificacaoService::homologar` | email + sms | Encarregados (prioridade 3) |
| `juri_designado` | `EventoService::atribuirJuri` | sistema + email | Jurado/pronunciador |
| `cms_submetida` | `NoticiaService` (submeter) | sistema | Grupo editor_noticias |
| `cms_publicada` / `cms_devolvida` | `NoticiaService` | sistema | Autor |
| `cms_comentario_pendente` | `ComentarioService` | sistema | Grupo editor_noticias |
| `capacitacao_confirmada` | Módulo de capacitações | sms + email | Participante |

Adicionar um evento novo = criar o(s) template(s) `{evento}_{canal}` no
backoffice + uma chamada `notificar()` no service de domínio. Zero
alterações na camada de notificações.

## 6. Checklist de integração

1. Copiar ficheiros para `app/`; registar em `Config/Services.php`:
   `notificador`, `templateRenderer`, `filaNotificacoes`, `canalEmail`,
   `canalSms`, `canalSistema`, `smsProvider` (este escolhe
   `Pro2SmsProvider` ou `NuloProvider` conforme `pro2sms.ativo`).
2. Configurar SMTP em `Config/Email`/.env (host, porta 587/TLS, credenciais).
3. `.env` da pro2sms (bloco acima) e afinar os 3 pontos `[AJUSTAR]`.
4. Criar a view `Views/emails/layout_base.php` (layout HTML institucional
   com o logotipo — entregue na Fase 8).
5. Rota do webhook: `POST api/sms/callback → Api\SmsCallbackController::receber`
   (fora do CSRF; já coberto pela exceção `api/*` da Fase 4).
6. Cron: `notificacoes:processar` a cada minuto (já listado com o do CMS).
7. Chaves `Notificacoes.*` em `Language/pt-AO/Notificacoes.php`.
8. Templates dos eventos do catálogo (os 4 principais já vêm no seed da
   v2.0; criar os restantes no backoffice).
