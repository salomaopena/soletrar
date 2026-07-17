# Roteiros de Aulas Tutoriais — por Perfil
## Concurso Nacional de Soletração — Angola

Cada roteiro é uma aula prática, feita **dentro do sistema real** (ou de um ambiente de teste com uma edição de exemplo). Os **checkpoints** não são perguntas de teoria — são tarefas: a pessoa só avança quando o sistema confirmar visualmente que a ação resultou.

> **Antes de começar qualquer roteiro:** confirme que a pessoa já tem conta criada (`Configurações → Utilizadores`) e, se for coordenador, **território atribuído** (`Atribuições`). Sem isto, nenhum roteiro funciona — é sempre a primeira coisa a verificar.

### Índice

1. Superadministrador / Gestor de Sistema
2. Coordenador Nacional
3. Coordenador Provincial (e Municipal)
4. Coordenador Escolar / Professor
5. Presidente do Júri
6. Jurado
7. Pronunciador
8. Editor de Notícias / Jornalista
9. Encarregado de Educação

---

## 1. Superadministrador / Gestor de Sistema

**🎯 Objetivo:** preparar tecnicamente uma edição nova do zero.
**⏱ Duração:** ~60 min · **🔑 Acesso:** grupo `superadmin`

| # | Passo | ✅ Checkpoint |
|---|---|---|
| 1 | `Configurações → Edições → Nova edição`. Preencher nome, ano, datas de inscrição. | A edição aparece na listagem com estado **planeamento**. |
| 2 | `Configurações → Categorias → Nova`. Criar 2 categorias (ex.: A e B) ligadas à edição. | As duas categorias aparecem na listagem, com classe mín./máx. corretas. |
| 3 | `Configurações → Fases → Nova`. Criar Escolar (vagas=3) → Provincial (vagas=2) → Final Nacional (vagas=0), por esta ordem. | As 3 fases aparecem ordenadas 1, 2, 3. |
| 4 | `Configurações → Utilizadores → Adicionar utilizador`. Criar uma conta de coordenador escolar. | O novo utilizador aparece na listagem, com o grupo certo. |
| 5 | Na mesma listagem, clicar **Atribuições** desse utilizador e dar-lhe uma escola. | A atribuição aparece marcada como **ativa**. |
| 6 | Voltar a `Edições`, editar a edição criada no passo 1, mudar estado para **inscrições abertas**. | Ao visitar `/inscricao` no portal público, o formulário aparece (não a mensagem "inscrições encerradas"). |
| 7 | Correr `php spark app:diagnostico` na linha de comandos. | Termina com "✓ Nenhum problema encontrado". |

**🆘 Se algo correr mal:** ver secção 8 do Manual Operacional ("O que fazer quando..."). Este roteiro é pré-requisito de **todos** os outros — nenhum dos roteiros seguintes funciona sem estes 7 passos feitos por alguém.

---

## 2. Coordenador Nacional

**🎯 Objetivo:** acompanhar a edição do princípio ao fim e fechar o ano.
**⏱ Duração:** ~40 min (mais o acompanhamento real durante a época) · **🔑 Acesso:** grupo `coord_nacional`

| # | Passo | ✅ Checkpoint |
|---|---|---|
| 1 | `Painel`. Ler os números do topo (inscrições, validadas, pendentes). | Consegue dizer, sem ajuda, quantas inscrições há na edição ativa. |
| 2 | `Admin → Relatórios`. Escolher a edição no seletor. | O funil por província carrega, com a barra de progresso de validação visível. |
| 3 | `Admin → Progressões`. Filtrar por uma fase. | A lista mostra candidatos, fase de origem e destino. |
| 4 | Pedir a um colega para homologar um evento de teste (ou simular). Voltar a Progressões. | O(s) novo(s) apurado(s) aparece(m) na lista, com "homologado por" preenchido. |
| 5 | `Admin/Eventos/{id}/Prémios` num evento de Final Nacional concluído. Clicar **Atribuir prémios pendentes**. | O estado dos prémios muda de "Pendente" para "Atribuído". |
| 6 | `Configurações → Edições`, editar a edição do ano, mudar estado para **Encerrado**. | A edição deixa de aparecer nas listas de "eventos ativos" mas continua visível em Relatórios e Resultados. |

**🆘 Se algo correr mal:** empates bloqueando homologação → ver roteiro 5 (Presidente do Júri), secção de desempate.

---

## 3. Coordenador Provincial (e Municipal)

**🎯 Objetivo:** validar inscrições e conduzir o evento provincial.
**⏱ Duração:** ~50 min · **🔑 Acesso:** grupo `coord_provincial` (ou `coord_municipal`), com território atribuído

| # | Passo | ✅ Checkpoint |
|---|---|---|
| 1 | `Admin → Inscrições`. Confirmar que só aparecem inscrições da sua província. | Nenhuma inscrição de outra província é visível. |
| 2 | Abrir uma inscrição pendente → **Validar**. | O estado muda para "Validada" e o encarregado recebe notificação (verificar em `Configurações → Registo de envios`). |
| 3 | `Admin → Eventos → Novo evento`. Fase = Provincial, categoria, província (a sua). | O evento aparece na listagem, sem aviso de duplicado. |
| 4 | Na sala de controlo do evento, clicar **Confirmar elegíveis**. | A lista de participantes enche-se automaticamente com os apurados das escolas dessa província. |
| 5 | Atribuir Presidente + Pronunciador do júri. | Os 4 indicadores de prontidão ficam verdes; o botão **Iniciar evento** desbloqueia. |
| 6 | Depois do evento decorrido (ou simulado): **Concluir evento** → **Homologar**. | O botão vira o selo "Já homologado"; em Progressões aparecem os novos apurados para a fase seguinte. |

**🆘 Se algo correr mal:** "Confirmar elegíveis" traz zero pessoas → normalmente é porque nenhum evento escolar dessa província foi homologado ainda.

---

## 4. Coordenador Escolar / Professor

**🎯 Objetivo:** inscrever candidatos e conduzir o evento escolar do início ao fim.
**⏱ Duração:** ~50 min · **🔑 Acesso:** grupo `coord_escolar` ou `professor`, com a escola atribuída

| # | Passo | ✅ Checkpoint |
|---|---|---|
| 1 | `Admin → Inscrições → Nova inscrição`. Preencher um candidato de teste + encarregado. | Aparece o comprovativo com o **número de inscrição** (nunca "TEMP-..."). |
| 2 | `Admin → Eventos → Novo evento`. Fase = Escolar, categoria, a sua escola. | O evento aparece na listagem. |
| 3 | Na sala de controlo, **Confirmar elegíveis**. | O candidato inscrito no passo 1 aparece na lista de participantes (se já validado). |
| 4 | No dia do evento: marcar presença de pelo menos 2 candidatos. | O contador "Pelo menos 2 presentes" fica verde. |
| 5 | **Banco de palavras**: confirmar que há palavras **validadas** suficientes para a classe da categoria. | Ao "Montar conjunto", o sistema não devolve erro de palavras insuficientes. |
| 6 | Atribuir júri (Presidente + Pronunciador — pode ser a própria pessoa a acumular, num evento pequeno). | Prontidão 4/4 verde; **Iniciar evento** desbloqueia. |
| 7 | Abrir o Palco, correr pelo menos 1 round completo com os candidatos de teste. | A grelha de resultados mostra as tentativas registadas. |
| 8 | **Concluir evento**. | A classificação aparece calculada, com o vencedor em 1.º lugar. |

**🆘 Se algo correr mal:** ver roteiro 6/7 para os detalhes do palco em si.

---

## 5. Presidente do Júri

**🎯 Objetivo:** conduzir o palco, gerir rounds, decidir apelações e desempates.
**⏱ Duração:** ~35 min · **🔑 Acesso:** grupo `jurado` (papel "presidente" atribuído ao evento)

| # | Passo | ✅ Checkpoint |
|---|---|---|
| 1 | `Admin → Palco` do evento. Ler a faixa de estado no topo. | Sabe dizer se há ou não um round em curso, só de olhar. |
| 2 | **Abrir round** — escolher tipo "eliminatório", dificuldade "média". | A faixa muda para "Round #1 em curso · dificuldade média". |
| 3 | Chamar um candidato, deixar o júri avaliar como errado. | O candidato fica marcado como eliminado nesse round. |
| 4 | Solicitar uma **apelação** sobre essa tentativa, depois decidir "Aceite". | A eliminação é revertida e os pontos creditados — confirme na lista de sobreviventes. |
| 5 | **Concluir round** com pelo menos uma tentativa por avaliar (propositadamente). | O sistema recusa concluir e diz quantas tentativas faltam. Avaliar a que falta, tentar de novo. | 
| 6 | Simular um empate na classificação final (dois candidatos com os mesmos pontos/tempo). Tentar homologar. | O sistema bloqueia com "empate nas posições de qualificação". |
| 7 | Abrir um **novo round tipo "Desempate"** só para esses dois. Concluir. | Depois de recalcular, a homologação deixa de bloquear. |

**🆘 Se algo correr mal:** "Abrir round" diz que já existe um, mas o ecrã parece vazio → recarregar a página (o estado é sempre lido do servidor).

---

## 6. Jurado

**🎯 Objetivo:** avaliar tentativas com justiça e rapidez.
**⏱ Duração:** ~20 min · **🔑 Acesso:** grupo `jurado`

| # | Passo | ✅ Checkpoint |
|---|---|---|
| 1 | No Palco, aguardar o pronunciador chamar um candidato. | O cartão da palavra aparece do lado do pronunciador (o júri não vê a palavra antes da resposta). |
| 2 | Ouvir a soletração, escrever a resposta dada no campo. | O campo "Soletração dada" está preenchido antes de decidir. |
| 3 | Clicar **Correto** ou **Incorreto** conforme a decisão do júri (não conforme a sugestão do sistema). | A decisão fica registada — nunca se pode voltar atrás sem apelação. |
| 4 | Praticar registar um **pedido** do candidato (ex.: "Pediu Definição") antes de decidir. | O botão de pedido fica marcado visualmente. |

**🆘 Se algo correr mal:** decisão errada por engano → não há "desfazer" direto; a correção formal é por apelação (ver roteiro 5).

---

## 7. Pronunciador

**🎯 Objetivo:** ler a palavra e apoiar o candidato com definição/exemplo, sem influenciar a decisão.
**⏱ Duração:** ~20 min · **🔑 Acesso:** grupo `pronunciador`

| # | Passo | ✅ Checkpoint |
|---|---|---|
| 1 | Chamar um candidato da lista de sobreviventes. | O cartão mostra: palavra, silabação, definição, exemplo, etimologia, notas. |
| 2 | Praticar ler as **notas para o pronunciador** antes de dizer a palavra em voz alta. | Consegue identificar homófonas ou avisos de pronúncia registados na palavra. |
| 3 | Responder a um pedido de "Definição" lendo o campo correspondente. | O candidato (simulado) recebe a definição correta, tal como está gravada na ficha da palavra. |

**🆘 Se algo correr mal:** palavra sem definição/exemplo preenchidos → reportar ao Banco de Palavras para completar a ficha (ver roteiro 1 ou 4, secção de palavras).

---

## 8. Editor de Notícias / Jornalista

**🎯 Objetivo:** publicar uma notícia do princípio ao fim, incluindo agendamento.
**⏱ Duração:** ~30 min · **🔑 Acesso:** grupo `editor_noticias` ou `jornalista`

| # | Passo | ✅ Checkpoint |
|---|---|---|
| 1 | `Admin → Notícias → Nova notícia`. Preencher título, resumo, conteúdo. Guardar. | Aparece como "Rascunho" na listagem. |
| 2 | Escolher uma categoria e adicionar 2 etiquetas. Guardar de novo. | As etiquetas aparecem na notícia publicada mais tarde. |
| 3 | **Jornalista:** clicar "Submeter para revisão". **Editor:** clicar "Publicar agora". | O estado muda para "Publicada" e a notícia aparece no portal público em `/noticias`. |
| 4 | Criar uma segunda notícia e usar "Agendar publicação" para 5 minutos no futuro. | Ao fim desse tempo (com o cron a correr), a notícia publica-se sozinha. |
| 5 | `Admin → Media`. Enviar uma imagem com texto alternativo preenchido. | A imagem aparece na biblioteca e pode ser escolhida como imagem destacada de uma notícia. |
| 6 | `Configurações → Menus`. Adicionar essa notícia ao menu do cabeçalho. | O link aparece na navegação do portal público. |

**🆘 Se algo correr mal:** notícia agendada não publica sozinha → confirmar que a tarefa `cms:publicar-agendados` está no cron do servidor.

---

## 9. Encarregado de Educação

**🎯 Objetivo:** inscrever o seu educando e acompanhar o processo, sem precisar de conta de administração.
**⏱ Duração:** ~15 min · **🔑 Acesso:** nenhum (portal público)

| # | Passo | ✅ Checkpoint |
|---|---|---|
| 1 | Ir a `/inscricao` no portal público. Preencher os dados do candidato e do encarregado. | Ao submeter, aparece uma página de comprovativo com o número de inscrição. |
| 2 | Guardar (ou clicar) o link "Acompanhar estado" desse comprovativo. | A página mostra o estado atual (Pendente/Validada/Rejeitada). |
| 3 | Verificar a caixa de e-mail (e SMS, se aplicável). | Recebeu a mensagem de confirmação da inscrição. |
| 4 | Depois de um evento homologado, visitar `/resultados`. | Consegue encontrar a edição, a fase, e ver a classificação do seu educando. |

**🆘 Se algo correr mal:** não recebeu SMS/e-mail → normal se o sistema estiver em ambiente de testes (o envio real de SMS pode estar desligado); confirmar com o coordenador escolar o estado da inscrição diretamente no sistema.

---

## Como usar estes roteiros numa formação real

- **Ordem sugerida de formação, por grupo de pessoas:** primeiro o roteiro 1 (quem prepara o sistema), depois 2–4 (coordenadores, por ordem de fase), depois 5–7 juntos numa única sessão de "ensaio geral" do palco (são interdependentes — precisam de estar todos ao mesmo tempo para fazer sentido), depois 8 e 9 podem correr em paralelo, a qualquer altura.
- **Ensaio geral obrigatório antes do 1.º evento real:** juntar quem vai fazer os roteiros 4, 5, 6 e 7 na mesma sala, com um evento de teste, e correr os passos todos seguidos — é isto que separa uma equipa preparada de uma a aprender ao vivo, à frente dos candidatos.
- Cada checkpoint falhado é uma oportunidade de formação, não um problema — se alguém não conseguir confirmar um checkpoint, é sinal de que precisa de repetir esse passo antes de avançar.
