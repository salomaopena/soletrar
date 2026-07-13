# Concurso Nacional de Soletração — Angola

Aplicação web em **CodeIgniter 4 + Shield + MySQL 8** para gerir o
Concurso Nacional de Soletração de Angola: inscrições, execução das provas
(escolar → provincial → nacional), banco de palavras, CMS editorial e
notificações multi-canal (sistema, e-mail, SMS via pro2sms.ao).

## Requisitos

- PHP 8.1+ (extensões: intl, mbstring, json, mysqlnd, curl, gd)
- Composer 2
- MySQL 8.0+ ou MariaDB 10.5+
- Servidor web (Apache/Nginx) ou `php spark serve` para desenvolvimento

## Instalação

```bash
# 1. Dependências
composer install

# 2. Ambiente
cp env .env
#   Editar .env: base de dados, chaves, SMTP e pro2sms.
#   Definir também (para o superadmin inicial):
#     superadmin.email = admin@soletracao.ao
#     superadmin.senha = <senha forte>

# 3. Chaves de encriptação (DUAS, distintas)
php spark key:generate            # preenche encryption.key
php spark key:generate --show     # copiar o valor para urlcrypt.chave no .env

# 4. Base de dados
#    Criar a BD vazia definida no .env (ex.: CREATE DATABASE soletracao ...)
php spark migrate                 # cria as 53 tabelas, views e triggers
php spark db:seed InicialSeeder   # províncias + superadmin
php spark cache:clear             # limpar a cache dos menus

# 5. Arrancar (desenvolvimento)
php spark serve
#    Aceder a http://localhost:8080
```

## Tarefas agendadas (produção)

```cron
* * * * * cd /caminho/projeto && php spark notificacoes:processar >> /dev/null 2>&1
* * * * * cd /caminho/projeto && php spark cms:publicar-agendados   >> /dev/null 2>&1
```

## Estrutura

```bash
app/
  Config/          Configuração (App, Services, AuthGroups, Filters, Rotas/, ...)
  Controllers/     Público, Admin (por módulo), Api
  Services/        Regras de negócio: Comum, Seguranca, Concurso, Cms, Notificacoes
  Models/          Um por tabela de negócio
  Entities/        Candidato, Noticia, Palavra
  Filters/         Escopo territorial, auditoria, rate limit
  Helpers/         data, texto, url_crypt, formato
  Libraries/Sms/   Integração pro2sms (provider substituível)
  Views/           layouts, components (reutilizáveis), publico, admin, emails
  Database/        Migrations, Seeds, sql/schema_v2.sql (fonte de verdade do esquema)
  Language/pt-AO/  Textos (idioma base) — en/ preparado para i18n
public/            Front controller + assets (tema, imagens)
writable/          Cache, logs, sessões, uploads privados
tests/             Testes (unit, database)
```

## Estado da implementação

**Implementado e funcional:**

- Fundações: datas (UTC↔Luanda), configuração, uuid, escopo, auditoria,
  encriptação de URL, uploads seguros.
- Segurança: autorização em 2 camadas (Shield + escopo), validação,
  rate limit, CSRF.
- Módulo do concurso (services): inscrição, palavras, tentativas, eventos,
  rounds, classificação, progressão, relatórios.
- Módulo CMS (services + notícias): máquina de estados, media, menus,
  comentários.
- Notificações: fachada, fila com retries, e-mail, SMS/pro2sms, webhook.
- Frontend: tema, layouts, componentes, página de resultados.
- Fluxo completo ponta-a-ponta: **inscrição pública**.

**Stubs (seguir os padrões, marcados com `// TODO`):**

- CRUDs administrativos de: escolas, eventos, palavras (interface),
  media (interface), páginas, comentários (interface), utilizadores,
  auditoria, configurações, fila de notificações.
- Estes controllers têm o esqueleto e apontam para os controllers de
  referência já implementados (`Admin\Inscricoes\InscricoesController`
  para CRUD com escopo/cifra; `Admin\Cms\NoticiasController` para fluxo
  de estados). As views usam os componentes prontos da Fase 8.

Ver `docs/` para os documentos de projeto (Fases 1–10) com todas as
decisões e justificações, e `docs/FASE10_ROTEIRO.md` para o plano de
desenvolvimento das partes ainda em stub.

## Antes do primeiro concurso real

O **ensaio geral do palco ao vivo** (descrito na Fase 10) é obrigatório:
simulação completa com júri, teste de falha de rede e contingência em
papel. O palco é o subsistema de maior risco.
