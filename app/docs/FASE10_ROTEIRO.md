# FASE 10 — ROTEIRO DE DESENVOLVIMENTO
## Concurso Nacional de Soletração — Angola

> Fase final. Converte tudo o que foi projetado (Fases 1–9) num plano de
> execução: iterações priorizadas, dependências, riscos e testes.

---

## 1. Estado atual — o que já existe

Antes do plano, o ponto de partida. As Fases 1–9 entregaram a **fundação
completa e um vertical slice funcional**:

- Banco de dados v2.0 (53 tabelas, 3 views, 4 triggers) — SQL de referência.
- Camadas transversais implementadas: datas, configuração, uuid, escopo,
  auditoria, encriptação de URL, uploads.
- Segurança: autorização em duas camadas, validação, rate limit, CSRF.
- Módulo do concurso (services de negócio: inscrição, palavras, tentativas,
  eventos, rounds, classificação, progressão, relatórios).
- Módulo CMS (notícias com máquina de estados, media, menus, comentários).
- Notificações (fachada, fila, retries, e-mail, SMS/pro2sms, webhook DLR).
- Frontend (tema, layouts, componentes, página de resultados).
- Estrutura executável: rotas, migrations de exemplo, Services consolidado,
  inscrição pública ponta-a-ponta.

O que falta é sobretudo **repetição de padrões já demonstrados** (CRUDs
restantes), a **interface do palco ao vivo** (a peça de maior risco) e o
**endurecimento para produção** (testes, seeds reais, deploy).

---

## 2. Princípios do roteiro

1. **Vertical slices, não camadas horizontais.** Cada iteração entrega
   uma funcionalidade utilizável de ponta a ponta, não "todos os models"
   seguidos de "todos os controllers". O slice da inscrição (Fase 9) é o
   modelo.
2. **Seguir o calendário real do concurso.** A ordem de construção espelha
   a ordem de uso: primeiro inscrever, depois preparar palavras, depois
   competir, depois publicar resultados. Assim, cada época do ano encontra
   a funcionalidade pronta.
3. **O palco ao vivo é o coração e o maior risco** — recebe uma iteração
   dedicada, um ensaio real e um plano de contingência.
4. **Testar o que dói se falhar**, não perseguir cobertura. Ver secção 6.

---

## 3. Iterações

Estimativas em semanas de **um programador CI4 sénior a tempo inteiro**;
ajustar à equipa real. As iterações 0–6 são o caminho crítico até um
concurso real poder correr.

### Iteração 0 — Fundação executável · 1 sem
**Objetivo:** o projeto arranca, migra e autentica.
- Converter o SQL v2.0 em migrations completas (a Fase 9 deu o padrão e a
  ordem); correr `shield:setup` + `migrate`.
- Seeders base: províncias (feito), grupos/permissões Shield, categorias
  de palavras e de notícias, configurações, templates de notificação,
  superadmin.
- Fundir os excertos de config (`Services`, `Validation`, `Filters`,
  `Routes`, `Pager`) e o `.env`.
- Registar helpers no `Autoload`.
**Saída:** login funcional, painel vazio, base migrada.
**Dependências:** nenhuma. **Risco:** baixo.

### Iteração 1 — Geografia e utilizadores · 1,5 sem
**Objetivo:** administrar o território e quem lá opera.
- CRUD de províncias/municípios/escolas (import CSV de escolas — haverá
  centenas).
- Gestão de utilizadores + atribuição de coordenadores por nível
  (`coordenadores_atribuicao`) — é o que faz o `EscopoService` funcionar
  com dados reais.
- Ecrã "sem atribuição" e fluxo de convite de coordenadores.
**Dependências:** Iteração 0.
**Risco:** médio — a qualidade dos dados de escolas condiciona tudo a
jusante. Mitigação: validação de import + deduplicação.

### Iteração 2 — Edições, categorias e fases · 1 sem
**Objetivo:** configurar uma edição do concurso.
- CRUD de edição (com datas de inscrição/prazos), categorias por
  classe/idade, fases (escolar → provincial → nacional) com
  `vagas_proxima_fase`.
- É pré-requisito de dados para a inscrição já existente (Fase 9).
**Dependências:** Iteração 0.
**Risco:** baixo.

### Iteração 3 — Inscrições completas · 1,5 sem
**Objetivo:** fechar o ciclo de inscrição.
- O fluxo público já existe (Fase 9); falta o **lado administrativo**:
  listagem com escopo (existe o controller de referência), detalhe,
  validação/rejeição em massa, edição, exportação.
- Inscrição assistida pelo coordenador escolar (mesma `InscricaoService`).
- Upload de documentos (usa o `UploadService`, perfil privado).
**Dependências:** Iterações 1 e 2.
**Risco:** médio — RN-01/RN-02/RN-03 já cobertas por testes (secção 6).

### Iteração 4 — Banco de palavras · 1,5 sem
**Objetivo:** ter munição para os eventos.
- CRUD de palavras (definição, silabação, exemplo, etimologia, áudio,
  notas do pronunciador), categorias, níveis de classe, validação
  pedagógica em duas etapas.
- Importação em lote + gravação/upload de áudio.
- Montagem e pré-visualização de pool por evento (`PalavraService`).
**Dependências:** Iteração 0 (independente de 1–3; pode correr em paralelo).
**Risco:** médio — sem palavras suficientes e validadas, não há evento.
Começar cedo o povoamento é uma tarefa de conteúdo, não de código.

### Iteração 5 — PALCO AO VIVO · 2,5 sem ⚠️ crítica
**Objetivo:** conduzir um evento em tempo real.
- Interface da mesa do júri sobre o `PalcoController` (JSON/AJAX já feito):
  abrir round, chamar candidato, cartão do pronunciador, cronómetro,
  botões acerto/erro, pedidos, apelações.
- **Ecrã de projeção pública** separado (nunca mostra a palavra antes da
  avaliação) — sincronizado por polling curto ou SSE.
- Modo offline-tolerante: fila de ações local que ressincroniza (ver
  Riscos).
**Dependências:** Iterações 2 e 4.
**Risco:** ALTO — concorrência, tempo real, falha de rede em palco, uso
sob pressão por não-técnicos. Mitigação: iteração dedicada + ensaio geral
(secção 5) + contingência em papel.

### Iteração 6 — Classificação, progressão e resultados · 1,5 sem
**Objetivo:** apurar, homologar, publicar.
- Ecrã de desempate; homologação com verificação de empates nas vagas.
- Progressão automática para a fase seguinte + repescagens manuais.
- Página pública de resultados (view da Fase 8) + grelha de rounds.
- Notificações de resultados aos encarregados (já integradas).
**Dependências:** Iteração 5.
**Risco:** médio — a correção dos critérios de ordenação é crítica e
testável (secção 6).

### Iteração 7 — CMS e portal público · 2 sem
**Objetivo:** comunicação institucional.
- Backoffice editorial completo (o `NoticiaService` e a máquina de estados
  existem): editor rico, media library, categorias/tags, agendamento,
  comentários, páginas, menus.
- Home e portal público, SEO (sitemap/feed), newsletter.
**Dependências:** Iteração 0 (independente do concurso; paraleliza).
**Risco:** baixo.

### Iteração 8 — Notificações em produção · 1 sem
**Objetivo:** e-mail e SMS reais e monitorizados.
- Afinar os 3 pontos `[AJUSTAR]` da pro2sms com credenciais reais; testar
  entrega e webhook DLR.
- Backoffice de fila/logs (reenfileirar falhados, custos de SMS).
- Configurar SMTP de produção + cron dos workers.
**Dependências:** Iteração 3 (primeiros disparos reais vêm das inscrições).
**Risco:** médio — dependência de terceiro (pro2sms). Mitigar cedo, com o
`NuloProvider` a cobrir o desenvolvimento entretanto.

### Iteração 9 — Capacitações e prémios · 1 sem
- Formações de professores/jurados/pronunciadores, presenças, certificados.
- Prémios e patrocinadores.
**Risco:** baixo.

### Iteração 10 — Endurecimento e lançamento · 2 sem
- Auditoria de segurança (OWASP básico), revisão de escopo em todos os
  CRUDs, teste de carga na inscrição e no palco.
- Backups automáticos, monitorização, página de manutenção.
- Documentação de operação + formação das coordenações.
- Acessibilidade (revisão AA) e i18n (extração de strings restantes).

**Caminho crítico até um concurso real:** Iterações 0→1→2→3→4→5→6
(~10,5 semanas). CMS (7), capacitações/prémios (9) e parte das
notificações (8) **paralelizam** se houver mais do que um programador.

---

## 4. Mapa de dependências

```
        ┌── 0 Fundação
        │      │
        │      ├── 1 Geografia+Utilizadores ──┐
        │      ├── 2 Edições/Fases ───────────┼── 3 Inscrições ── 8 Notif. prod.
        │      ├── 4 Banco de palavras ───────┤
        │      │                              └── 5 PALCO ── 6 Resultados
        │      ├── 7 CMS/Portal (paralelo)
        │      └── 9 Capacitações/Prémios (paralelo)
        └──────────────────────────────────── 10 Endurecimento (transversal, fecha)
```

Regra de leitura: 4 pode arrancar assim que 0 estiver pronto (é conteúdo,
começar já); 7 e 9 não bloqueiam o caminho crítico; 10 acompanha desde o
início mas concentra-se no fim.

---

## 5. O ensaio geral (obrigatório antes do 1.º evento real)

O palco é o único subsistema onde uma falha é pública e irreversível. Antes
do primeiro evento a valer:

1. **Simulação completa** com 12–16 "candidatos" de teste, júri real e o
   ecrã de projeção, do início ao fim, incluindo apelações e um desempate.
2. **Teste de falha de rede** a meio de um round: desligar o Wi-Fi e
   confirmar que a mesa continua e ressincroniza.
3. **Contingência em papel**: uma folha de registo imprimível gerada pelo
   sistema, para o júri continuar se tudo falhar; os dados entram depois.
4. **Formação da mesa**: presidente, pronunciador e secretário treinam a
   interface até ser muscular — sob pressão, ninguém lê tooltips.

---

## 6. Testes prioritários (o que dói se falhar)

Não perseguir cobertura; blindar o que causa dano real ou injustiça.

### Testes de regra de negócio (unitários dos services) — prioridade máxima
- **RN-01**: candidato inscrito sempre na província da escola; tentativa de
  forçar outra província é ignorada.
- **RN-02**: segunda inscrição na mesma edição é recusada (service + UNIQUE).
- **RN-03**: classe/idade fora da categoria → rejeição; idade calculada à
  data de referência da edição (não "hoje").
- **Número de inscrição**: sequencial por edição+província, sem colisões
  sob concorrência (teste com lock).
- **Classificação**: ordenação por sobrevivência > pontos > tempo; empate
  nas vagas bloqueia homologação.
- **Progressão**: só os N primeiros qualificam; reprocessar é idempotente.
- **Apelação aceite**: reverte eliminação e credita pontos, uma só vez.

### Testes de segurança — prioridade máxima
- Token de URL: adulterado/de outro contexto/expirado → 404, nunca decifra.
- Escopo: coordenador de província A não acede a registo da província B
  (listagem E detalhe E escrita).
- Permissões: cada rota admin exige a permissão correta.

### Testes de integração — prioridade alta
- Fluxo de inscrição pública completo (feliz + cada validação a falhar).
- Fila de notificações: falha do provedor → retry com backoff → falhada.
- Transição editorial: jornalista submete, não publica; editor publica.

### Testes manuais/E2E — prioridade alta
- O ensaio geral do palco (secção 5) é o E2E mais importante do projeto.
- Responsividade da grelha de resultados em telemóvel.

Meta pragmática: **services de negócio com testes unitários sólidos**
(é onde vive a correção e a justiça do concurso); controllers e views
cobertos por alguns testes de integração e pelo ensaio. Configurar CI para
correr a suite a cada push desde a Iteração 0.

---

## 7. Riscos principais e mitigações

| Risco | Impacto | Prob. | Mitigação |
|---|---|---|---|
| Falha de rede no palco ao vivo | Alto | Média | Modo offline-tolerante + contingência em papel + ensaio |
| Palavras insuficientes/validadas a tempo | Alto | Média | Iteração 4 cedo; povoamento é tarefa de conteúdo contínua |
| Integração pro2sms diferente do previsto | Médio | Média | Isolada atrás de interface + 3 pontos `[AJUSTAR]`; NuloProvider cobre o resto |
| Dados de escolas incompletos/duplicados | Médio | Alta | Import validado + deduplicação na Iteração 1 |
| Uso por coordenadores não-técnicos | Médio | Alta | UI simples (Fase 8), formação, mensagens de erro claras |
| Conectividade fraca nas províncias | Médio | Alta | Assets locais (não CDN), páginas leves, tolerância a latência |
| Empates e casos de regulamento não previstos | Médio | Média | Progressão/repescagem manual auditável já existe; alinhar com o regulamento oficial cedo |
| Pico de inscrições no prazo final | Médio | Média | Rate limit + índices já definidos; teste de carga na Iteração 10 |

---

## 8. Recomendações finais

- **Alinhar o regulamento oficial cedo.** Pontuação por dificuldade,
  critérios de desempate, número de vagas por fase e regras de apelação
  são configuráveis, mas precisam de decisão institucional antes da
  Iteração 5. O sistema está preparado; o regulamento é que manda.
- **Começar o banco de palavras já.** É a única tarefa que não é de
  programação e que pode bloquear um evento. Uma equipa pedagógica pode
  povoá-lo em paralelo com o desenvolvimento desde a Iteração 0.
- **Fazer um piloto numa só província** antes do lançamento nacional. Uma
  edição escolar+provincial reduzida valida o sistema inteiro com risco
  contido, e o feedback informa o endurecimento (Iteração 10).
- **Manter o SQL v2.0 e estes documentos como fonte de verdade** à medida
  que o código evolui: cada decisão relevante tem aqui a sua justificação,
  o que acelera a integração de novos programadores.

O sistema foi projetado para durar além da base inicial: novas edições
anuais são dados, não código; novos canais de notificação, provedores de
SMS ou idiomas são extensões previstas nas interfaces. A fundação está
sólida — o roteiro acima leva-a a produção.
