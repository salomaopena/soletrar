# Concurso Nacional de Soletração — Angola
## Índice geral dos entregáveis (Fases 1–10)

Sistema web completo em CodeIgniter 4 + Shield + MySQL 8, projetado para
gerir o Concurso Nacional de Soletração de Angola: inscrições, execução
das provas (escolar → provincial → nacional), banco de palavras, CMS
editorial estilo WordPress e notificações multi-canal (sistema, e-mail,
SMS via pro2sms.ao).

---

## Como ler esta entrega

Cada fase produziu um **documento** (`FASEx_*.md`) com as decisões e
justificações, e a maioria produziu também um **pacote de código**
(`fasex_*_codigo.zip`) com ficheiros PHP prontos a colocar em `app/`.
Comece pelo documento de cada fase; o código é a materialização do que
o documento explica.

Sugestão de leitura por perfil:
- **Decisor/coordenação:** Fases 1 e 10 (visão e plano).
- **Arquiteto/tech lead:** Fases 2, 3, 4 e 9.
- **Programador:** todos os `.zip`, começando pela Fase 9 (estrutura) e
  o vertical slice da inscrição.
- **Designer/frontend:** abrir `preview_tema.html` no browser + Fase 8.

---

## Mapa de entregáveis

| Fase | Tema | Documento | Código |
|---|---|---|---|
| 1 | Levantamento e consolidação | *(na resposta de abertura)* | — |
| 2 | Modelagem do banco de dados | *(na resposta)* | `concurso_soletracao_angola_v2.sql` |
| 3 | Arquitetura CodeIgniter 4 | `FASE3_ARQUITETURA_CI4.md` | — |
| 4 | Segurança e reutilização | `FASE4_SEGURANCA.md` | `fase4_seguranca_codigo.zip` |
| 5 | Módulo CMS / Notícias | `FASE5_CMS.md` | `fase5_cms_codigo.zip` |
| 6 | Módulo do concurso | `FASE6_CONCURSO.md` | `fase6_concurso_codigo.zip` |
| 7 | Notificações (e-mail/SMS) | `FASE7_NOTIFICACOES.md` | `fase7_notificacoes_codigo.zip` |
| 8 | Frontend com Bootstrap | `FASE8_FRONTEND.md` | `fase8_frontend_codigo.zip` + `preview_tema.html` |
| 9 | Estrutura inicial do código | `FASE9_ESTRUTURA.md` | `fase9_estrutura_codigo.zip` |
| 10 | Roteiro de desenvolvimento | `FASE10_ROTEIRO.md` | — |

---

## Banco de dados

`concurso_soletracao_angola_v2.sql` — 53 tabelas, 3 views, 4 triggers,
com seeds das 21 províncias, categorias, configurações e templates de
notificação. É a **fonte de verdade** do modelo; no projeto converte-se em
migrations (padrão e ordem em `ORDEM_MIGRATIONS.md`, dentro do zip da
Fase 9).

Principais melhorias da v2.0 sobre o modelo original: camada de
notificações completa (`notificacoes_fila`, `logs_email`, `logs_sms`,
`notificacoes_templates`), tabela `progressoes_fase` para rastrear a
qualificação entre fases, identificadores públicos `uuid`, correção de
`menus_itens` e novos triggers/constraints de integridade.

---

## Arquitetura em uma página

```
Request → Rotas (público|auth|admin) → Filtros (sessão, escopo, auditoria,
throttle) → Controller fino → Service (regras de negócio) → Model/Entity → View

Camadas transversais (Services/Comum + Seguranca):
  datas (UTC↔Luanda) · configuração · uuid · escopo territorial ·
  auditoria · encriptação de URL · uploads seguros

Domínio:
  Concurso  — inscrição, palavras, tentativas, eventos, rounds,
              classificação, progressão, relatórios
  CMS       — notícias (máquina de estados), media, menus, comentários
  Notificações — fachada única → fila (retries) → sistema/email/SMS(pro2sms)
```

Decisões estruturantes: controllers finos e services transacionais;
autorização em duas camadas (permissão Shield + escopo territorial);
IDs sensíveis cifrados na URL com contexto e TTL; notificações 100%
assíncronas com provedor de SMS substituível; datas sempre em UTC com
conversão única na apresentação.

---

## Arranque rápido

```bash
composer install
composer require codeigniter4/shield ezyang/htmlpurifier
cp env.referencia .env            # preencher BD, chaves, SMTP, pro2sms
php spark key:generate            # encryption.key
php spark key:generate --show     # copiar para urlcrypt.chave no .env
php spark shield:setup
php spark migrate
php spark db:seed InicialSeeder

# cron (produção):
#  * * * * * php spark notificacoes:processar
#  * * * * * php spark cms:publicar-agendados
```

---

## Próximo passo recomendado

Seguir o roteiro da Fase 10, começando pela Iteração 0 (fundação
executável) e, **em paralelo desde já**, o povoamento do banco de palavras
(tarefa de conteúdo, não de código, que pode bloquear o primeiro evento).
Antes do primeiro concurso real, o ensaio geral do palco descrito na
Fase 10 é obrigatório.
