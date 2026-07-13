# FASE 6 — MÓDULO DO CONCURSO
## Concurso Nacional de Soletração — Angola

> Acompanha `fase6_concurso_codigo.zip` (11 ficheiros PHP).
> Este é o núcleo do domínio: os services desta fase são os únicos
> guardiões das regras RN-01 a RN-07.

---

## 1. Mapa dos entregáveis

| Componente | Papel | Regras que guarda |
|---|---|---|
| `InscricaoService` | Inscrição transacional (candidato+encarregado+inscrição), validação/rejeição | RN-01, RN-02, RN-03, RN-05 |
| `PalavraService` | Montagem do pool por evento e sorteio durante rounds | RN-06 |
| `TentativaService` | Vez do candidato, pedidos, avaliação do júri, apelações | RN-07 |
| `EventoService` | Júri, confirmação de participantes, pré-condições de início | — |
| `RoundService` | Abertura/fecho de rounds, sobreviventes | — |
| `ClassificacaoService` | Cálculo de posições, homologação | Publicação só após homologação |
| `ProgressaoService` | Qualificação para a fase seguinte, repescagens | RN-04 |
| `RelatorioService` | Estatísticas, classificações, grelha de rounds, palavras difíceis | Escopo territorial |
| `PalcoController` | Mesa do júri ao vivo (JSON/AJAX) | — |
| `InscricaoModel`, entity `Candidato` | Persistência com escopos; idade e nome de palco | — |

## 2. Fluxo de inscrição

```
Formulário (público ou coordenador escolar)
   │  validação de formulário (regras nomeadas + RegrasAngola)
   ▼
InscricaoService::inscrever()          ← UMA transação
   1. prazo de inscrições da edição (datas comparadas em UTC)
   2. RN-03: classe ∈ [min,max] da categoria; idade à DATA DE
      REFERÊNCIA DA EDIÇÃO (não "hoje" — validação estável todo o período)
   3. RN-05: autorização do encarregado assinada
   4. RN-01 POR CONSTRUÇÃO: a província deriva da escola escolhida —
      o formulário nem envia provincia_id, eliminando a inconsistência
      na origem (o trigger da Fase 2 continua como última defesa)
   5. cria candidato (uuid) → encarregado principal → verifica RN-02
      (única inscrição/edição; a UNIQUE do BD é a rede final) → inscrição
   6. número: ANO-COD-SEQ com GET_LOCK nomeado por edição+província
      (serializa apenas concorrentes do mesmo balcão; sem MAX()+1 nu)
   ▼  fora da transação (falha de SMS não desfaz inscrição)
Notificação ao encarregado: e-mail com link de acompanhamento
   (id cifrado com TTL 72 h — Fase 4) + fila de SMS
```

Validação/rejeição pelo coordenador exige `exigirEscopo()` (Fase 4) e só
opera sobre estado `pendente`; ambas notificam o encarregado principal.

## 3. Fluxo do evento ao vivo (a mesa do júri)

```
confirmarParticipantes()   ← elegíveis: fase escolar = inscrições validadas
        │                    da escola; fases seguintes = progressoes_fase
        ▼                    para ESTA fase (idempotente: reexecutar não duplica)
registarPresenca()         ← check-in no dia
        ▼
iniciar()                  ← pré-condições: presidente + pronunciador no júri,
        │                    pool com palavras, ≥ 2 presentes
        ▼
┌─► abrir round (dificuldade, tempo, flags de pedidos)
│       ▼
│   para cada sobrevivente:
│     iniciarVez() → sorteia palavra do pool → cartão do pronunciador
│     registarPedido() (repetição/definição/etimologia/exemplo,
│                       se o round permitir)
│     avaliar() → resposta + decisão do juiz + tempo
│         acertou → pontos por dificuldade (configurável)
│         errou em eliminatório → eliminado_round = N
│     [apelação: solicitar → juiz de apelação decide;
│      aceite reverte eliminação E credita pontos, tudo rastreado]
│       ▼
│   concluir round (bloqueia se houver tentativas por avaliar)
└── enquanto sobreviventes > 1
        ▼
concluir evento → ClassificacaoService::calcular()
        ▼
HOMOLOGAÇÃO (coordenador) → progressão + resultados públicos + notificações
```

Decisões importantes do palco:

- **A comparação automática é sugestão, a decisão do juiz prevalece.** O
  sistema compara com `palavra_normalizada` e mostra ✓/✗ sugerido, mas o
  campo `correta` só é escrito com a decisão humana (`juiz_decisao_id`).
  Num concurso oral há pronúncias, hífenes ditados, ruído — a autoridade é
  do júri; o sistema regista.
- **Nada se apaga (RN-07).** Erros de mesa corrigem-se por apelação, com
  motivo e juiz registados. `tentativas_soletracao` é o histórico oficial.
- **Palavra nunca chega ao ecrã público antes da avaliação** — o
  `PalcoController` devolve o cartão completo apenas ao modo pronunciador;
  a projeção pública (Fase 8) consome um endpoint separado sem a palavra.
- **Controller "burro" com respostas JSON** — o palco atualiza por AJAX;
  recarregar páginas durante um evento ao vivo não é aceitável.

## 4. Pool de palavras (RN-06)

`montarPool(eventoId, ['media' => 40, 'dificil' => 25, ...])` seleciona
palavras **validadas**, adequadas ao intervalo de classes da categoria,
que **ainda não foram usadas em nenhum evento da mesma edição** — regra
deliberada: candidatos de um evento podem assistir a outro; repetir
palavras dentro da edição comprometeria a equidade. A falta de palavras
falha ANTES do evento, com contagem exata do défice por dificuldade.
A marcação `usada` e a estatística global ficam a cargo do trigger
`trg_tentativa_estatistica_palavra` (v2.0).

## 5. Classificação e desempate

Critérios (nesta ordem): sobreviveu até mais tarde (`eliminado_round`
DESC, NULL primeiro) → maior pontuação → menor tempo total. Empates
absolutos partilham a posição (ex-aequo). A **homologação bloqueia** se
existir empate dentro das vagas de qualificação (`empateNasVagas`) —
obrigando ao round de desempate humano antes de selar. Só a homologação
torna os resultados públicos e dispara a progressão.

## 6. Progressão entre fases (RN-04)

`apurarQualificados()` corre na homologação: os N primeiros
(`vagas_proxima_fase`) ganham um registo em `progressoes_fase`
(fase origem, evento, posição, quem homologou). A operação é
**idempotente** (UNIQUE inscrição+fase destino com `INSERT IGNORE`).
Casos excecionais — desistência de um qualificado, convite, substituição
— passam por `progredirManual()`, que exige tipo próprio e observação
obrigatória: a exceção fica tão auditável quanto a regra.
`confirmarParticipantes()` da fase seguinte lê exatamente esta tabela.

## 7. Relatórios

- **Estatísticas por província** (funil inscrições→validadas), limitadas
  ao escopo do utilizador;
- **Classificação de evento** — alimenta a página pública de resultados;
- **Grelha de rounds** — uma linha por candidato, uma célula por round
  (✓/✗/apelação), a estrutura de dados da experiência
  spellingbee.com/round-results pedida no briefing (render na Fase 8);
- **Palavras mais difíceis da edição** — via `v_historico_uso_palavras`
  (v2.0), com amostra mínima de 3 usos; valor pedagógico para as
  capacitações.

## 8. Checklist de integração

1. Copiar ficheiros para `app/`; registar em `Config/Services.php`:
   `inscricoes`, `palavras`, `tentativas`, `eventos`, `rounds`,
   `classificacao`, `progressao`, `relatorios`.
2. `service('uuid')` → registar wrapper de `Ramsey\Uuid` (ou
   `bin2hex(random_bytes())` formatado) — usado em candidatos/inscrições.
3. Chaves `Concurso.*` em `Language/pt-AO/Concurso.php` (lista completa
   nos docblocks dos services).
4. Configuração `pontos_por_dificuldade` (seed em `configuracoes`, tipo
   json) — defaults 1..5 já embutidos.
5. Rotas do `PalcoController` no grupo admin com
   `permission:concurso.juri.avaliar`; homologação com
   `permission:concurso.resultados.homologar`.
6. Templates de notificação usados nesta fase (já semeados na v2.0):
   `inscricao_recebida_email`, `inscricao_validada_sms`,
   `evento_convocatoria_sms`, `resultado_publicado_email`.
