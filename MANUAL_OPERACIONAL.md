# Manual Operacional
## Concurso Nacional de Soletração — Angola

**Do início da edição ao encerramento da fase nacional.**
Este manual segue a ordem real em que as coisas acontecem, do primeiro clique de janeiro ao último em dezembro. Cada secção diz **quem faz**, **onde clicar** e **o que verificar** antes de avançar.

---

## 0. Papéis — quem faz o quê

| Papel | Âmbito | Faz principalmente |
|---|---|---|
| **Superadministrador** | Nacional | Tudo — inclui criar outros superadmins |
| **Coordenador Nacional** | Nacional | Configura a edição, homologa a fase nacional, atribui território a coordenadores |
| **Coordenador Provincial** | 1 província | Cria/conduz eventos provinciais, valida inscrições da sua província |
| **Coordenador Municipal** | 1 município | Idem, à escala municipal |
| **Coordenador Escolar / Professor** | 1 escola | Inscreve candidatos, conduz o evento escolar |
| **Jurado / Pronunciador** | Por evento | Avalia e conduz o palco no dia |
| **Editor de Notícias** | Nacional | Publica notícias, geri menus e páginas |

> Sem **atribuição de território** (`Configurações → Utilizadores → Atribuições`), um coordenador não vê absolutamente nada — é uma proteção deliberada, não um bug. É sempre o primeiro coisa a verificar quando alguém "não vê nada".

---

## 1. ANTES da época — preparar a edição (uma vez por ano)

Esta secção só é feita **uma vez**, tipicamente no início do ano letivo. Sem isto, nada do resto funciona.

**Checklist, por esta ordem:**

- [ ] **`Configurações → Edições → Nova edição`** — nome, ano, datas de abertura/fecho de inscrições, classe/idade mínima e máxima. Estado inicial: `planeamento`.
- [ ] **`Configurações → Categorias`** — as faixas por classe (ex.: Categoria A = 1.ª–4.ª, Categoria B = 5.ª–8.ª), ligadas a esta edição.
- [ ] **`Configurações → Fases`** — normalmente 3: **Escolar** (`vagas_proxima_fase` = quantos apuram por escola, ex.: 3) → **Provincial** (`vagas_proxima_fase` = quantos apuram por província, ex.: 2) → **Final Nacional** (`vagas_proxima_fase` = 0, não há progressão depois desta).
- [ ] **`Configurações → Escolas` / `Municípios`** — confirmar que as escolas participantes existem e estão ativas.
- [ ] **`Configurações → Utilizadores → Adicionar utilizador`** — criar as contas dos coordenadores, com o grupo certo. Logo a seguir, em **Atribuições**, dar-lhes o território (escola, município ou província).
- [ ] **Banco de palavras** — criar palavras (`Banco de palavras → Nova palavra`) e **validá-las** (`Banco de palavras → selecionar → Validar selecionadas`). **Só palavras validadas entram em concurso.** Comece isto cedo — é a única tarefa aqui que não é "clicar num botão", é trabalho pedagógico de verdade.
- [ ] **`Configurações → Locais`** — espaços físicos dos eventos provinciais/nacionais, se aplicável.
- [ ] **`Configurações → Parcerias`** — patrocinadores e prémios (pode fazer-se agora ou mais tarde, mas os prémios têm de existir antes da fase nacional acabar).

Quando estiver tudo pronto, mude o estado da edição para **`inscricoes_abertas`** — é isto que liberta o formulário público em `/inscricao`.

---

## 2. Inscrições

**Onde:** portal público (`/inscricao`) para encarregados/professores, ou `Admin → Inscrições → Nova inscrição` para inscrição assistida pelo coordenador escolar (mesma lógica, mesmas regras).

**O que o sistema garante sozinho:**
- a província do candidato é sempre a da escola escolhida (não se escolhe à parte);
- um candidato não se inscreve duas vezes na mesma edição;
- a classe tem de bater com a categoria escolhida;
- o encarregado tem de confirmar a autorização.

**Depois de receber inscrições:** `Admin → Inscrições` (barra de estados: Pendente / Validada / Rejeitada). Um coordenador só vê as inscrições do seu território. Validar dispara notificação automática (SMS + e-mail) ao encarregado.

**Para consultar/imprimir:** `Admin → Pesquisar candidatos` — filtros combináveis (nome, escola, categoria, estado...), com botões **Imprimir lista** (pronta a assinar) e **Exportar (Excel)**.

Quando o prazo acabar, mude o estado da edição para **`inscricoes_fechadas`**.

---

## 3. Fase Escolar — um evento por escola, por categoria

Aqui entra-se no ciclo que se repete em todas as fases (escolar, provincial, nacional) — vale a pena perceber bem esta secção, porque as outras duas são a mesma coisa noutra escala.

### 3.1 Criar o evento

`Admin → Eventos → Novo evento` — fase = Escolar, categoria, escola, data. **O sistema recusa criar** um segundo evento ativo para a mesma combinação fase+categoria+escola — se isso acontecer, é sinal de que já existe um e deve usá-lo (ou cancelá-lo primeiro, se for mesmo para substituir).

### 3.2 Preparar (sala de controlo do evento)

Na página do evento (`Ver`), quatro coisas têm de ficar verdes antes de poder iniciar:

1. **Júri** — atribuir, no mínimo, um **Presidente** e um **Pronunciador**.
2. **Participantes** — botão **"Confirmar elegíveis"**: traz automaticamente as inscrições validadas dessa escola/categoria. (O sistema também impede que o mesmo candidato apareça em dois eventos ativos da mesma fase.)
3. **Presenças** — no dia, marcar quem está `presente` (mínimo 2).
4. **Conjunto de palavras (pool)** — **"Montar conjunto"** por dificuldade, ou **"Ver conteúdo"** para escolher palavras à mão. Só entram palavras validadas, adequadas à classe da categoria, ainda não usadas nesta edição.

Quando os 4 pontos estiverem prontos, o botão **"Iniciar evento"** desbloqueia.

### 3.3 Conduzir no Palco

`Admin → Palco` (ou o atalho a partir do evento). Fluxo:

1. **Abrir round** — escolher tipo (eliminatório / classificatório / **desempate** / final) e dificuldade.
2. Clicar num candidato da lista de sobreviventes → o sistema sorteia uma palavra do conjunto e mostra o cartão completo ao pronunciador (nunca ao ecrã de projeção, antes de avaliar).
3. Registar pedidos do candidato (repetição, definição, etimologia, exemplo), se o round os permitir.
4. O júri decide **Correto / Incorreto** — a decisão humana manda sempre; o sistema só sugere.
5. Se as palavras dessa dificuldade acabarem, o aviso diz exatamente o que fazer: adicionar mais, mudar de dificuldade, ou concluir o round.
6. Uma palavra **errada** pode ser devolvida ao conjunto (botão "Devolver" na página do pool) para sair de novo noutro round — nunca uma palavra acertada.
7. **Concluir round** quando todas as tentativas estiverem avaliadas. Repetir até restar um vencedor (ou até decidir por classificação de pontos).
8. **Concluir evento** — a classificação é calculada automaticamente (sobrevivência → pontos → tempo; quem nunca chegou a soletrar fica sempre em último, nunca à frente de quem competiu).

### 3.4 Fechar o ciclo

- Se a classificação precisar de correção (ex.: presença mal marcada), há um botão **"Recalcular classificação"** na sala de controlo — não é preciso repetir o evento.
- **Homologar** (`concurso.resultados.homologar`): sela os resultados, publica-os no portal e **apura automaticamente** os N primeiros para a fase seguinte (conforme `vagas_proxima_fase`). Bloqueia se houver empate dentro das vagas — nesse caso, abra um round de **desempate** antes.
- **A homologação só pode acontecer uma vez por evento.** Depois disso o botão vira um selo "Já homologado".
- Repita 3.1–3.4 para **cada escola** participante.

---

## 4. Fase Provincial — a agregação automática

Não há uma "lista" separada de quem vai para o provincial — é a própria homologação de cada evento escolar que alimenta isso.

1. `Admin → Eventos → Novo evento` — fase = Provincial, categoria, província (não escola desta vez).
2. Na sala de controlo, **"Confirmar elegíveis"** traz automaticamente os apurados de **todas as escolas dessa província**, cruzando com as homologações já feitas.
3. Daqui para a frente, é exatamente o mesmo ciclo da secção 3.3 (júri → pool → palco → concluir → homologar).

Para ver quem passou de onde para onde, a qualquer momento: `Admin → Progressões` (filtrável por fase). Casos excecionais — desistência, convite, repescagem — usam o formulário de **progressão manual** nessa mesma página, com motivo obrigatório.

---

## 5. Fase Nacional — semifinal e final

Mesmo ciclo outra vez, agora à escala do país (fase = Semifinal Nacional, depois Final Nacional). A diferença: a **Final Nacional** tem `vagas_proxima_fase = 0` — a homologação não apura mais ninguém, porque não há para onde ir.

Depois de concluído e homologado o evento da Final Nacional, aparece o atalho **"Prémios"** na sala de controlo.

---

## 6. Prémios

`Evento (concluído) → Prémios`. A página cruza automaticamente os prémios configurados (`Configurações → Prémios`, por posição/categoria/fase) com quem ficou em cada posição **neste evento**. Botão **"Atribuir prémios pendentes"** grava tudo de uma vez. **"Lista de premiados"** fica pronta a imprimir para a cerimónia.

---

## 7. Encerrar a edição

Este é o último passo do ano, e é literal: `Configurações → Edições → editar a edição → Estado → `**`Encerrado`**.

Antes de o fazer, confirme:

- [ ] Todos os eventos da Final Nacional estão **concluídos e homologados**.
- [ ] Os prémios foram **atribuídos** e a lista de premiados foi impressa/entregue.
- [ ] `Admin → Relatórios` — vale a pena tirar aqui o retrato final da edição (inscrições por província, por classe, por género, escolas com mais inscrições, palavras mais difíceis) antes de arquivar mentalmente o ano.
- [ ] `Resultados` (portal público) — confirme que a listagem pública está completa e correta para todas as fases.

Depois de marcar `Encerrado`, a edição fica como registo histórico. **Uma nova edição não colide nunca com esta** — cada edição tem as suas próprias fases e eventos, mesmo reutilizando as mesmas escolas e categorias do ano anterior. Para o ano seguinte, volte à secção 1.

---

## 8. Referência rápida — "O que fazer quando..."

| Situação | Onde resolver |
|---|---|
| Coordenador não vê nada | `Configurações → Utilizadores → Atribuições` — falta território |
| Palavras insuficientes no pool | Mensagem já diz o motivo exato; normalmente faltam validar palavras |
| Empate nas vagas de apuramento | Abrir round tipo **Desempate** no Palco, com os candidatos empatados |
| Classificação parece errada | Botão **Recalcular classificação** no evento |
| Não consigo criar um evento | O sistema bloqueou por já existir um ativo igual — cancele o antigo ou edite-o |
| Preciso de corrigir uma progressão errada | `Admin → Progressões → Remover`, depois lançar a correta manualmente |
| Newsletter/e-mails não chegam | `Configurações → Registo de envios` mostra o estado e o erro de cada mensagem |
| Diagnóstico geral do sistema | `php spark app:diagnostico` (linha de comandos) |

---

## 9. Glossário de estados

**Edição:** planeamento → inscrições abertas → inscrições fechadas → em curso → final → **encerrado** (ou cancelado)
**Inscrição:** pendente → validada / rejeitada
**Evento:** agendado → em curso → pausado/adiado → **concluído** (→ homologado, sem campo próprio — verifica-se pelo selo na página)
**Round:** eliminatório · classificatório · desempate · final
**Palavra:** por validar → validada (só assim entra em concurso)

---

*Próximo documento: roteiros de aulas tutoriais com checkpoints, para formar coordenadores e júris antes do primeiro evento real.*
