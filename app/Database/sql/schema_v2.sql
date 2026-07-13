-- =====================================================================
-- CONCURSO NACIONAL DE SOLETRAÇÃO - ANGOLA
-- Banco de Dados Completo — VERSÃO 2.0 (revisto e melhorado)
-- SGBD: MySQL 8.0+ / MariaDB 10.5+
-- Charset: utf8mb4 (suporte completo a acentuação portuguesa)
-- Framework: CodeIgniter 4 + Shield (autenticação)
-- Modelo de referência: Scripps Spelling Bee + African Spelling Bee
--
-- PRINCIPAIS MELHORIAS DESTA VERSÃO (v2.0):
--   1. Camada completa de notificações multi-canal: templates,
--      fila com retries (notificacoes_fila), logs_email e logs_sms
--      (integração pro2sms.ao).
--   2. Tabela progressoes_fase: rastreabilidade auditável da
--      qualificação de candidatos entre fases (escolar → provincial
--      → nacional).
--   3. Identificadores públicos não sequenciais (uuid) em candidatos
--      e inscricoes, para suportar URLs com parâmetros
--      encriptados/assinados sem expor IDs.
--   4. Correção de inconsistência em menus_itens (faltava noticia_id).
--   5. CHECK constraints adicionais (intervalos de classes coerentes).
--   6. Trigger de estatística de uso de palavras (contador
--      usada_em_concursos + pool marcado como usado).
--   7. Slug de notícias passa a ser responsabilidade da aplicação
--      (helper com transliteração completa) — trigger simplista removido.
--   8. Configurações seed para SMS, e-mail e timezone (Africa/Luanda).
--   9. Estratégia de timestamps documentada: TODAS as datas são
--      gravadas em UTC; a conversão para Africa/Luanda (UTC+1) é
--      feita na camada de apresentação (service DataHora).
-- =====================================================================

-- [migration] removido: SET NAMES utf8mb4;
-- [migration] removido: SET FOREIGN_KEY_CHECKS = 0;

-- [migration] removido: CREATE DATABASE IF NOT EXISTS `soletracao_angola`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- [migration] removido: USE `soletracao_angola`;

-- =====================================================================
-- SECÇÃO 1: TABELAS DO CODEIGNITER SHIELD (AUTENTICAÇÃO)
-- =====================================================================
-- NOTA: Estas tabelas são criadas automaticamente pelas migrações do Shield
-- (php spark shield:setup). Estão aqui apenas para referência e para
-- mostrar como o sistema se integra com elas. NÃO execute esta secção
-- se já correu as migrações do Shield.
-- =====================================================================

-- =====================================================================
-- SECÇÃO 2: ESTRUTURA GEOGRÁFICA (ANGOLA)
-- =====================================================================

CREATE TABLE `provincias` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome`        VARCHAR(80) NOT NULL,
  `codigo`      VARCHAR(5) NOT NULL COMMENT 'Sigla curta, ex: LDA, HLA',
  `capital`     VARCHAR(80) NULL,
  `regiao`      ENUM('Norte','Centro','Sul','Leste','Oeste','Capital') NULL,
  `ativo`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NULL,
  `updated_at`  DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provincia_nome` (`nome`),
  UNIQUE KEY `uq_provincia_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='As 21 províncias de Angola (divisão administrativa de 2024)';

CREATE TABLE `municipios` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provincia_id` INT UNSIGNED NOT NULL,
  `nome`         VARCHAR(120) NOT NULL,
  `ativo`        TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   DATETIME NULL,
  `updated_at`   DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_municipios_provincia` (`provincia_id`),
  UNIQUE KEY `uq_municipio_provincia` (`provincia_id`, `nome`),
  CONSTRAINT `fk_municipios_provincia`
    FOREIGN KEY (`provincia_id`) REFERENCES `provincias` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECÇÃO 3: ESCOLAS
-- =====================================================================

CREATE TABLE `escolas` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `municipio_id`     INT UNSIGNED NOT NULL,
  `provincia_id`     INT UNSIGNED NOT NULL COMMENT 'Desnormalizado para queries rápidas',
  `nome`             VARCHAR(180) NOT NULL,
  `tipo`             ENUM('publica','privada','comparticipada') NOT NULL DEFAULT 'publica',
  `subsistema`       ENUM('ensino_geral','tecnico_profissional','formacao_professores') NOT NULL DEFAULT 'ensino_geral',
  `nivel`            SET('primario','i_ciclo','ii_ciclo') NOT NULL DEFAULT 'primario,i_ciclo'
                     COMMENT 'Primário (1-6) + I Ciclo (7-9) cobre até 8.ª classe',
  `endereco`         VARCHAR(255) NULL,
  `telefone`         VARCHAR(30) NULL,
  `email`            VARCHAR(120) NULL,
  `diretor_nome`     VARCHAR(150) NULL,
  `numero_alunos`    INT UNSIGNED NULL,
  `latitude`         DECIMAL(10, 7) NULL,
  `longitude`        DECIMAL(10, 7) NULL,
  `ativo`            TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`       DATETIME NULL,
  `updated_at`       DATETIME NULL,
  `deleted_at`       DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_escolas_municipio` (`municipio_id`),
  KEY `idx_escolas_provincia` (`provincia_id`),
  KEY `idx_escolas_tipo` (`tipo`),
  CONSTRAINT `fk_escolas_municipio`
    FOREIGN KEY (`municipio_id`) REFERENCES `municipios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_escolas_provincia`
    FOREIGN KEY (`provincia_id`) REFERENCES `provincias` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECÇÃO 4: PERFIS DE UTILIZADOR (estende auth do Shield)
-- =====================================================================
-- Tipos de utilizador (mapeados aos grupos do Shield):
--   superadmin            - administrador da plataforma
--   coord_nacional        - coordenador nacional
--   coord_provincial      - coordenador da província
--   coord_escolar         - coordenador da escola
--   professor             - professor responsável
--   jurado                - membro do júri
--   pronunciador          - pronunciador (lê as palavras)
--   encarregado           - encarregado de educação
--   candidato             - aluno concorrente
--   editor_noticias       - editor de notícias
--   jornalista            - autor de notícias
-- =====================================================================

CREATE TABLE `perfis_utilizador` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED NOT NULL COMMENT 'FK para users do Shield',
  `nome_completo`    VARCHAR(180) NOT NULL,
  `genero`           ENUM('M','F','outro','nao_informar') NULL,
  `data_nascimento`  DATE NULL,
  `bi_numero`        VARCHAR(30) NULL COMMENT 'Bilhete de Identidade',
  `telefone`         VARCHAR(30) NULL,
  `telefone_alt`     VARCHAR(30) NULL,
  `endereco`         VARCHAR(255) NULL,
  `provincia_id`     INT UNSIGNED NULL,
  `municipio_id`     INT UNSIGNED NULL,
  `foto`             VARCHAR(255) NULL,
  `bio`              TEXT NULL,
  `idiomas`          VARCHAR(255) NULL COMMENT 'CSV: portugues,umbundu,kimbundu,kikongo...',
  `created_at`       DATETIME NULL,
  `updated_at`       DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perfis_user` (`user_id`),
  UNIQUE KEY `uq_perfis_bi` (`bi_numero`),
  KEY `idx_perfis_provincia` (`provincia_id`),
  KEY `idx_perfis_municipio` (`municipio_id`),
  CONSTRAINT `fk_perfis_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_perfis_provincia`
    FOREIGN KEY (`provincia_id`) REFERENCES `provincias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_perfis_municipio`
    FOREIGN KEY (`municipio_id`) REFERENCES `municipios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Atribuição de coordenadores a províncias/municípios/escolas
CREATE TABLE `coordenadores_atribuicao` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED NOT NULL,
  `nivel`         ENUM('nacional','provincial','municipal','escolar') NOT NULL,
  `provincia_id`  INT UNSIGNED NULL,
  `municipio_id`  INT UNSIGNED NULL,
  `escola_id`     INT UNSIGNED NULL,
  `data_inicio`   DATE NOT NULL,
  `data_fim`      DATE NULL,
  `ativo`         TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`    DATETIME NULL,
  `updated_at`    DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_coord_user` (`user_id`),
  KEY `idx_coord_provincia` (`provincia_id`),
  KEY `idx_coord_municipio` (`municipio_id`),
  KEY `idx_coord_escola` (`escola_id`),
  CONSTRAINT `fk_coord_user`     FOREIGN KEY (`user_id`)      REFERENCES `users`       (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_coord_provincia` FOREIGN KEY (`provincia_id`) REFERENCES `provincias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_coord_municipio` FOREIGN KEY (`municipio_id`) REFERENCES `municipios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_coord_escola`    FOREIGN KEY (`escola_id`)    REFERENCES `escolas`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECÇÃO 5: BANCO DE PALAVRAS (núcleo pedagógico - modelo Scripps)
-- =====================================================================

CREATE TABLE `palavras_categorias` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome`         VARCHAR(100) NOT NULL,
  `descricao`    VARCHAR(255) NULL,
  `created_at`   DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_palavra_cat_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ex: Ciência, Geografia, História, Cultura Angolana, Literatura';

CREATE TABLE `palavras` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `palavra`            VARCHAR(120) NOT NULL,
  `palavra_normalizada` VARCHAR(120) NOT NULL COMMENT 'Sem acentos, minúscula, para comparação',
  `silabacao`          VARCHAR(180) NULL COMMENT 'Ex: con-cur-so',
  `classe_gramatical`  ENUM('substantivo','adjetivo','verbo','adverbio','pronome',
                            'preposicao','conjuncao','interjeicao','numeral','artigo','outro') NULL,
  `genero`             ENUM('masculino','feminino','comum','nao_aplicavel') NULL,
  `numero_silabas`     TINYINT UNSIGNED NULL,
  `definicao`          TEXT NOT NULL COMMENT 'Definição usada pelo pronunciador',
  `exemplo_uso`        TEXT NULL COMMENT 'Frase de exemplo',
  `etimologia`         VARCHAR(255) NULL COMMENT 'Origem da palavra (latim, grego, etc)',
  `idioma_origem`      VARCHAR(50) NULL,
  `pronuncia_ipa`      VARCHAR(120) NULL COMMENT 'Transcrição fonética IPA',
  `audio_url`          VARCHAR(255) NULL COMMENT 'Áudio com a pronúncia',
  `dificuldade`        ENUM('muito_facil','facil','media','dificil','muito_dificil') NOT NULL DEFAULT 'media',
  `nivel_minimo_classe` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1ª a 8ª classe',
  `nivel_maximo_classe` TINYINT UNSIGNED NOT NULL DEFAULT 8,
  `categoria_id`       INT UNSIGNED NULL,
  `regionalismo`       VARCHAR(80) NULL COMMENT 'Ex: Angolanismo, Brasileirismo',
  `fonte`              VARCHAR(255) NULL COMMENT 'Dicionário/obra de referência',
  `pagina_fonte`       VARCHAR(20) NULL,
  `notas_pronunciador` TEXT NULL,
  `homofonas`          VARCHAR(255) NULL COMMENT 'Palavras com som igual',
  `usada_em_concursos` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Contador',
  `taxa_acerto`        DECIMAL(5,2) NULL COMMENT 'Estatística de acerto histórico (%)',
  `validada`           TINYINT(1) NOT NULL DEFAULT 0,
  `validada_por`       INT UNSIGNED NULL,
  `criada_por`         INT UNSIGNED NULL,
  `created_at`         DATETIME NULL,
  `updated_at`         DATETIME NULL,
  `deleted_at`         DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_palavra` (`palavra`),
  KEY `idx_palavra_normalizada` (`palavra_normalizada`),
  KEY `idx_palavra_dificuldade` (`dificuldade`),
  KEY `idx_palavra_categoria` (`categoria_id`),
  KEY `idx_palavra_classe` (`nivel_minimo_classe`, `nivel_maximo_classe`),
  KEY `idx_palavra_validada` (`validada`),
  CONSTRAINT `fk_palavra_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `palavras_categorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_palavra_validada_por`
    FOREIGN KEY (`validada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_palavra_criada_por`
    FOREIGN KEY (`criada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_palavra_intervalo_classes`
    CHECK (`nivel_minimo_classe` <= `nivel_maximo_classe`),
  FULLTEXT KEY `ft_palavra_definicao` (`palavra`, `definicao`, `exemplo_uso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECÇÃO 6: EDIÇÕES E ESTRUTURA DO CONCURSO
-- =====================================================================

CREATE TABLE `edicoes_concurso` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ano`                 SMALLINT UNSIGNED NOT NULL,
  `nome`                VARCHAR(180) NOT NULL COMMENT 'Ex: I Concurso Nacional de Soletração 2026',
  `slug`                VARCHAR(200) NOT NULL,
  `tema`                VARCHAR(255) NULL COMMENT 'Tema/lema do ano',
  `descricao`           TEXT NULL,
  `data_abertura_inscricoes` DATE NULL,
  `data_encerramento_inscricoes` DATE NULL,
  `data_inicio`         DATE NULL,
  `data_fim`            DATE NULL,
  `status`              ENUM('planeamento','inscricoes_abertas','inscricoes_fechadas',
                             'em_curso','final','encerrado','cancelado') NOT NULL DEFAULT 'planeamento',
  `regulamento_url`     VARCHAR(255) NULL,
  `cartaz_url`          VARCHAR(255) NULL,
  `idade_minima`        TINYINT UNSIGNED NULL,
  `idade_maxima`        TINYINT UNSIGNED NULL,
  `classe_minima`       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `classe_maxima`       TINYINT UNSIGNED NOT NULL DEFAULT 8,
  `criada_por`          INT UNSIGNED NULL,
  `created_at`          DATETIME NULL,
  `updated_at`          DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_edicao_ano` (`ano`),
  UNIQUE KEY `uq_edicao_slug` (`slug`),
  KEY `idx_edicao_status` (`status`),
  CONSTRAINT `fk_edicao_criada_por`
    FOREIGN KEY (`criada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categorias por classe/idade dentro de cada edição
CREATE TABLE `categorias_competicao` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `edicao_id`      INT UNSIGNED NOT NULL,
  `nome`           VARCHAR(100) NOT NULL COMMENT 'Ex: Iniciados (1ª-3ª), Juvenis (4ª-6ª), Avançados (7ª-8ª)',
  `classe_minima`  TINYINT UNSIGNED NOT NULL,
  `classe_maxima`  TINYINT UNSIGNED NOT NULL,
  `idade_minima`   TINYINT UNSIGNED NULL,
  `idade_maxima`   TINYINT UNSIGNED NULL,
  `descricao`      TEXT NULL,
  `ordem`          TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`     DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cat_edicao` (`edicao_id`),
  CONSTRAINT `fk_cat_edicao`
    FOREIGN KEY (`edicao_id`) REFERENCES `edicoes_concurso` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_cat_intervalo_classes`
    CHECK (`classe_minima` <= `classe_maxima`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fases/etapas do concurso (eliminatórias locais → provinciais → nacional)
CREATE TABLE `fases_concurso` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `edicao_id`      INT UNSIGNED NOT NULL,
  `nome`           VARCHAR(120) NOT NULL,
  `tipo_fase`      ENUM('escolar','municipal','provincial','semifinal_nacional','final_nacional') NOT NULL,
  `ordem`          TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `data_inicio`    DATE NULL,
  `data_fim`       DATE NULL,
  `vagas_proxima_fase` INT UNSIGNED NULL COMMENT 'Quantos avançam por unidade (escola/município/província)',
  `descricao`      TEXT NULL,
  `regras_especificas` TEXT NULL,
  `status`         ENUM('agendada','em_curso','concluida','cancelada') NOT NULL DEFAULT 'agendada',
  `created_at`     DATETIME NULL,
  `updated_at`     DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fase_edicao` (`edicao_id`),
  KEY `idx_fase_tipo` (`tipo_fase`),
  CONSTRAINT `fk_fase_edicao`
    FOREIGN KEY (`edicao_id`) REFERENCES `edicoes_concurso` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECÇÃO 7: CANDIDATOS E INSCRIÇÕES
-- =====================================================================

CREATE TABLE `candidatos` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`            INT UNSIGNED NULL COMMENT 'NULL se candidato não tiver login',
  `numero_inscricao`   VARCHAR(20) NOT NULL COMMENT 'Gerado automaticamente, ex: 2026-LDA-00001',
  `uuid`               CHAR(36) NOT NULL COMMENT 'Identificador público não sequencial (usado em URLs, evita expor o ID)',
  `nome_completo`      VARCHAR(180) NOT NULL,
  `nome_preferido`     VARCHAR(80) NULL,
  `genero`             ENUM('M','F','outro') NOT NULL,
  `data_nascimento`    DATE NOT NULL,
  `bi_numero`          VARCHAR(30) NULL,
  `cedula_numero`      VARCHAR(30) NULL COMMENT 'Cédula pessoal para menores',
  `escola_id`          INT UNSIGNED NOT NULL,
  `provincia_id`       INT UNSIGNED NOT NULL COMMENT 'Província onde concorre - REGRA: imutável após inscrição',
  `municipio_id`       INT UNSIGNED NOT NULL,
  `classe_atual`       TINYINT UNSIGNED NOT NULL COMMENT 'Classe que frequenta (1-8)',
  `turma`              VARCHAR(20) NULL,
  `endereco`           VARCHAR(255) NULL,
  `telefone_contacto`  VARCHAR(30) NULL COMMENT 'Geralmente do encarregado',
  `email_contacto`     VARCHAR(120) NULL,
  `foto`               VARCHAR(255) NULL,
  `tem_necessidades_especiais` TINYINT(1) NOT NULL DEFAULT 0,
  `descricao_necessidades`     TEXT NULL,
  `idioma_materno`     VARCHAR(50) NULL,
  `outros_idiomas`     VARCHAR(255) NULL,
  `notas`              TEXT NULL,
  `ativo`              TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`         DATETIME NULL,
  `updated_at`         DATETIME NULL,
  `deleted_at`         DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_candidato_numero` (`numero_inscricao`),
  UNIQUE KEY `uq_candidato_uuid` (`uuid`),
  UNIQUE KEY `uq_candidato_user` (`user_id`),
  UNIQUE KEY `uq_candidato_bi` (`bi_numero`),
  UNIQUE KEY `uq_candidato_cedula` (`cedula_numero`),
  KEY `idx_candidato_escola` (`escola_id`),
  KEY `idx_candidato_provincia` (`provincia_id`),
  KEY `idx_candidato_municipio` (`municipio_id`),
  KEY `idx_candidato_classe` (`classe_atual`),
  CONSTRAINT `fk_candidato_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_candidato_escola`
    FOREIGN KEY (`escola_id`) REFERENCES `escolas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_candidato_provincia`
    FOREIGN KEY (`provincia_id`) REFERENCES `provincias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_candidato_municipio`
    FOREIGN KEY (`municipio_id`) REFERENCES `municipios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_candidato_classe`
    CHECK (`classe_atual` BETWEEN 1 AND 8)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Encarregados de educação
CREATE TABLE `encarregados_educacao` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `candidato_id`  INT UNSIGNED NOT NULL,
  `user_id`       INT UNSIGNED NULL,
  `nome_completo` VARCHAR(180) NOT NULL,
  `parentesco`    VARCHAR(40) NOT NULL COMMENT 'Pai, Mãe, Tio(a), Tutor...',
  `bi_numero`     VARCHAR(30) NULL,
  `telefone`      VARCHAR(30) NOT NULL,
  `telefone_alt`  VARCHAR(30) NULL,
  `email`         VARCHAR(120) NULL,
  `endereco`      VARCHAR(255) NULL,
  `profissao`     VARCHAR(120) NULL,
  `principal`     TINYINT(1) NOT NULL DEFAULT 1,
  `autorizou`     TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Termo de autorização assinado',
  `data_autorizacao` DATETIME NULL,
  `created_at`    DATETIME NULL,
  `updated_at`    DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_enc_candidato` (`candidato_id`),
  KEY `idx_enc_user` (`user_id`),
  CONSTRAINT `fk_enc_candidato`
    FOREIGN KEY (`candidato_id`) REFERENCES `candidatos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enc_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inscrições do candidato em edições do concurso
-- REGRA CRUCIAL: candidato só pode concorrer na província onde se inscreveu
CREATE TABLE `inscricoes` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`              CHAR(36) NOT NULL COMMENT 'Identificador público não sequencial (para URLs e comprovativos)',
  `candidato_id`      INT UNSIGNED NOT NULL,
  `edicao_id`         INT UNSIGNED NOT NULL,
  `categoria_id`      INT UNSIGNED NOT NULL,
  `provincia_id`      INT UNSIGNED NOT NULL COMMENT 'Província onde compete - imutável',
  `escola_id`         INT UNSIGNED NOT NULL,
  `data_inscricao`    DATETIME NOT NULL,
  `status`            ENUM('pendente','validada','rejeitada','desistencia','desclassificado') NOT NULL DEFAULT 'pendente',
  `motivo_rejeicao`   VARCHAR(255) NULL,
  `validada_por`      INT UNSIGNED NULL,
  `data_validacao`    DATETIME NULL,
  `documentos_url`    TEXT NULL COMMENT 'JSON com URLs dos documentos enviados',
  `observacoes`       TEXT NULL,
  `created_at`        DATETIME NULL,
  `updated_at`        DATETIME NULL,
  PRIMARY KEY (`id`),
  -- Um candidato só pode ter UMA inscrição por edição
  UNIQUE KEY `uq_inscricao_candidato_edicao` (`candidato_id`, `edicao_id`),
  UNIQUE KEY `uq_inscricao_uuid` (`uuid`),
  KEY `idx_insc_edicao` (`edicao_id`),
  KEY `idx_insc_provincia` (`provincia_id`),
  KEY `idx_insc_escola` (`escola_id`),
  KEY `idx_insc_categoria` (`categoria_id`),
  KEY `idx_insc_status` (`status`),
  CONSTRAINT `fk_insc_candidato`
    FOREIGN KEY (`candidato_id`) REFERENCES `candidatos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_insc_edicao`
    FOREIGN KEY (`edicao_id`) REFERENCES `edicoes_concurso` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_insc_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorias_competicao` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_insc_provincia`
    FOREIGN KEY (`provincia_id`) REFERENCES `provincias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_insc_escola`
    FOREIGN KEY (`escola_id`) REFERENCES `escolas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_insc_validada_por`
    FOREIGN KEY (`validada_por`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECÇÃO 8: EVENTOS, JÚRI E EXECUÇÃO DO CONCURSO
-- =====================================================================

CREATE TABLE `locais_evento` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome`          VARCHAR(180) NOT NULL,
  `provincia_id`  INT UNSIGNED NOT NULL,
  `municipio_id`  INT UNSIGNED NOT NULL,
  `endereco`      VARCHAR(255) NOT NULL,
  `capacidade`    INT UNSIGNED NULL,
  `latitude`      DECIMAL(10, 7) NULL,
  `longitude`     DECIMAL(10, 7) NULL,
  `contacto`      VARCHAR(120) NULL,
  `created_at`    DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_local_provincia` (`provincia_id`),
  KEY `idx_local_municipio` (`municipio_id`),
  CONSTRAINT `fk_local_provincia` FOREIGN KEY (`provincia_id`) REFERENCES `provincias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_local_municipio` FOREIGN KEY (`municipio_id`) REFERENCES `municipios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `eventos_competicao` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fase_id`        INT UNSIGNED NOT NULL,
  `categoria_id`   INT UNSIGNED NOT NULL,
  `provincia_id`   INT UNSIGNED NULL COMMENT 'NULL para fase nacional',
  `municipio_id`   INT UNSIGNED NULL,
  `escola_id`      INT UNSIGNED NULL COMMENT 'Para fase escolar',
  `local_id`       INT UNSIGNED NULL,
  `nome`           VARCHAR(180) NOT NULL,
  `data_evento`    DATETIME NOT NULL,
  `data_fim_prevista` DATETIME NULL,
  `status`         ENUM('agendado','em_curso','pausado','concluido','adiado','cancelado') NOT NULL DEFAULT 'agendado',
  `transmissao_url` VARCHAR(255) NULL COMMENT 'Link de transmissão ao vivo',
  `observacoes`    TEXT NULL,
  `created_at`     DATETIME NULL,
  `updated_at`     DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_evento_fase` (`fase_id`),
  KEY `idx_evento_categoria` (`categoria_id`),
  KEY `idx_evento_provincia` (`provincia_id`),
  KEY `idx_evento_data` (`data_evento`),
  CONSTRAINT `fk_evento_fase`      FOREIGN KEY (`fase_id`)      REFERENCES `fases_concurso`        (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evento_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_competicao` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_evento_provincia` FOREIGN KEY (`provincia_id`) REFERENCES `provincias`            (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_evento_municipio` FOREIGN KEY (`municipio_id`) REFERENCES `municipios`            (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_evento_escola`    FOREIGN KEY (`escola_id`)    REFERENCES `escolas`               (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_evento_local`     FOREIGN KEY (`local_id`)     REFERENCES `locais_evento`         (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Participação dos candidatos por evento (quem está confirmado em cada evento)
CREATE TABLE `participacoes` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `evento_id`      INT UNSIGNED NOT NULL,
  `inscricao_id`   INT UNSIGNED NOT NULL,
  `numero_concorrente` VARCHAR(10) NULL COMMENT 'Número usado no palco',
  `presenca`       ENUM('confirmada','presente','ausente','desistiu') NOT NULL DEFAULT 'confirmada',
  `posicao_final`  INT UNSIGNED NULL COMMENT 'Classificação final (1, 2, 3...)',
  `eliminado_round` INT UNSIGNED NULL COMMENT 'Round em que foi eliminado',
  `pontuacao_total` INT NOT NULL DEFAULT 0,
  `tempo_total_seg` INT UNSIGNED NULL,
  `observacoes`    TEXT NULL,
  `created_at`     DATETIME NULL,
  `updated_at`     DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_participacao` (`evento_id`, `inscricao_id`),
  KEY `idx_part_inscricao` (`inscricao_id`),
  KEY `idx_part_posicao` (`posicao_final`),
  CONSTRAINT `fk_part_evento`    FOREIGN KEY (`evento_id`)    REFERENCES `eventos_competicao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_part_inscricao` FOREIGN KEY (`inscricao_id`) REFERENCES `inscricoes`         (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Júri designado para cada evento
CREATE TABLE `juri_evento` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `evento_id`  INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `papel`      ENUM('presidente','jurado','pronunciador','juiz_apelacao','cronometrista','secretario') NOT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_juri_evento_user` (`evento_id`, `user_id`, `papel`),
  KEY `idx_juri_user` (`user_id`),
  CONSTRAINT `fk_juri_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos_competicao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_juri_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`              (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rounds dentro de um evento
CREATE TABLE `rounds_evento` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `evento_id`       INT UNSIGNED NOT NULL,
  `numero_round`    INT UNSIGNED NOT NULL,
  `tipo`            ENUM('eliminatorio','classificatorio','desempate','final') NOT NULL DEFAULT 'eliminatorio',
  `dificuldade`     ENUM('muito_facil','facil','media','dificil','muito_dificil') NOT NULL DEFAULT 'media',
  `tempo_limite_seg` INT UNSIGNED NOT NULL DEFAULT 60,
  `permite_repeticao` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Pode pedir para repetir a palavra',
  `permite_definicao` TINYINT(1) NOT NULL DEFAULT 1,
  `permite_etimologia` TINYINT(1) NOT NULL DEFAULT 1,
  `permite_exemplo`   TINYINT(1) NOT NULL DEFAULT 1,
  `iniciado_em`     DATETIME NULL,
  `concluido_em`    DATETIME NULL,
  `status`          ENUM('agendado','em_curso','concluido') NOT NULL DEFAULT 'agendado',
  `created_at`      DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_round_numero` (`evento_id`, `numero_round`),
  CONSTRAINT `fk_round_evento` FOREIGN KEY (`evento_id`) REFERENCES `eventos_competicao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tentativas de soletração (uma linha por palavra dada a um candidato)
-- ESTA É A TABELA CENTRAL DE EXECUÇÃO DO CONCURSO
CREATE TABLE `tentativas_soletracao` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `round_id`           INT UNSIGNED NOT NULL,
  `participacao_id`    INT UNSIGNED NOT NULL,
  `palavra_id`         INT UNSIGNED NOT NULL,
  `ordem_no_round`     INT UNSIGNED NOT NULL,
  `resposta_dada`      VARCHAR(150) NULL COMMENT 'O que o candidato soletrou',
  `correta`            TINYINT(1) NULL COMMENT 'NULL = ainda não avaliada',
  `tempo_resposta_seg` INT UNSIGNED NULL,
  `pediu_repeticao`    TINYINT(1) NOT NULL DEFAULT 0,
  `pediu_definicao`    TINYINT(1) NOT NULL DEFAULT 0,
  `pediu_etimologia`   TINYINT(1) NOT NULL DEFAULT 0,
  `pediu_exemplo`      TINYINT(1) NOT NULL DEFAULT 0,
  `apelacao_solicitada` TINYINT(1) NOT NULL DEFAULT 0,
  `apelacao_resultado` ENUM('aceite','rejeitada','pendente') NULL,
  `apelacao_motivo`    TEXT NULL,
  `juiz_decisao_id`    INT UNSIGNED NULL COMMENT 'User_id do juiz que decidiu',
  `pronunciador_id`    INT UNSIGNED NULL,
  `pontos_atribuidos`  INT NOT NULL DEFAULT 0,
  `audio_gravacao_url` VARCHAR(255) NULL,
  `iniciado_em`        DATETIME NULL,
  `respondido_em`      DATETIME NULL,
  `observacoes`        TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tent_round` (`round_id`),
  KEY `idx_tent_participacao` (`participacao_id`),
  KEY `idx_tent_palavra` (`palavra_id`),
  KEY `idx_tent_correta` (`correta`),
  CONSTRAINT `fk_tent_round`         FOREIGN KEY (`round_id`)        REFERENCES `rounds_evento`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tent_participacao`  FOREIGN KEY (`participacao_id`) REFERENCES `participacoes`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tent_palavra`       FOREIGN KEY (`palavra_id`)      REFERENCES `palavras`       (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_tent_juiz`          FOREIGN KEY (`juiz_decisao_id`) REFERENCES `users`          (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tent_pronunciador`  FOREIGN KEY (`pronunciador_id`) REFERENCES `users`          (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lista de palavras disponíveis para um evento (pool gerido pelo júri)
CREATE TABLE `pool_palavras_evento` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `evento_id`  INT UNSIGNED NOT NULL,
  `palavra_id` INT UNSIGNED NOT NULL,
  `usada`      TINYINT(1) NOT NULL DEFAULT 0,
  `ordem_sugerida` INT UNSIGNED NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pool_evento_palavra` (`evento_id`, `palavra_id`),
  CONSTRAINT `fk_pool_evento`  FOREIGN KEY (`evento_id`)  REFERENCES `eventos_competicao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pool_palavra` FOREIGN KEY (`palavra_id`) REFERENCES `palavras`           (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Progressão de candidatos entre fases do concurso
-- REGRA: a passagem escolar → provincial → nacional deve ser
-- rastreável e auditável. Cada linha regista COMO e QUANDO o
-- candidato se qualificou para a fase seguinte.
CREATE TABLE `progressoes_fase` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inscricao_id`         INT UNSIGNED NOT NULL,
  `fase_origem_id`       INT UNSIGNED NOT NULL COMMENT 'Fase em que se qualificou',
  `evento_origem_id`     INT UNSIGNED NULL COMMENT 'Evento concreto onde obteve a qualificação',
  `fase_destino_id`      INT UNSIGNED NOT NULL COMMENT 'Fase para a qual avança',
  `tipo`                 ENUM('qualificacao_direta','repescagem','convite','substituicao') NOT NULL DEFAULT 'qualificacao_direta',
  `posicao_qualificacao` INT UNSIGNED NULL COMMENT 'Posição obtida no evento de origem',
  `aprovada_por`         INT UNSIGNED NULL COMMENT 'Coordenador que homologou a progressão',
  `observacoes`          TEXT NULL,
  `created_at`           DATETIME NULL,
  PRIMARY KEY (`id`),
  -- Uma inscrição só pode progredir UMA vez para cada fase de destino
  UNIQUE KEY `uq_prog_inscricao_destino` (`inscricao_id`, `fase_destino_id`),
  KEY `idx_prog_fase_origem` (`fase_origem_id`),
  KEY `idx_prog_fase_destino` (`fase_destino_id`),
  KEY `idx_prog_evento_origem` (`evento_origem_id`),
  CONSTRAINT `fk_prog_inscricao`     FOREIGN KEY (`inscricao_id`)     REFERENCES `inscricoes`         (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prog_fase_origem`   FOREIGN KEY (`fase_origem_id`)   REFERENCES `fases_concurso`     (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prog_fase_destino`  FOREIGN KEY (`fase_destino_id`)  REFERENCES `fases_concurso`     (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prog_evento_origem` FOREIGN KEY (`evento_origem_id`) REFERENCES `eventos_competicao` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_prog_aprovada_por`  FOREIGN KEY (`aprovada_por`)     REFERENCES `users`              (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Rastreio auditável da progressão entre fases';

-- =====================================================================
-- SECÇÃO 9: PRÉMIOS E RECONHECIMENTO
-- =====================================================================

CREATE TABLE `premios` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `edicao_id`     INT UNSIGNED NOT NULL,
  `categoria_id`  INT UNSIGNED NULL,
  `fase_id`       INT UNSIGNED NULL,
  `posicao`       INT UNSIGNED NOT NULL COMMENT '1=ouro, 2=prata, 3=bronze',
  `nome`          VARCHAR(150) NOT NULL,
  `descricao`     TEXT NULL,
  `valor_monetario` DECIMAL(15,2) NULL,
  `moeda`         CHAR(3) NOT NULL DEFAULT 'AOA',
  `tipo`          ENUM('monetario','bolsa_estudo','material','troféu','medalha','certificado','outro') NOT NULL,
  `patrocinador_id` INT UNSIGNED NULL,
  `created_at`    DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_premio_edicao` (`edicao_id`),
  CONSTRAINT `fk_premio_edicao`    FOREIGN KEY (`edicao_id`)    REFERENCES `edicoes_concurso`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_premio_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_competicao` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_premio_fase`      FOREIGN KEY (`fase_id`)      REFERENCES `fases_concurso`        (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `premios_atribuidos` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `premio_id`     INT UNSIGNED NOT NULL,
  `participacao_id` INT UNSIGNED NOT NULL,
  `data_entrega`  DATE NULL,
  `observacoes`   TEXT NULL,
  `created_at`    DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_premio_part` (`premio_id`, `participacao_id`),
  CONSTRAINT `fk_pa_premio`       FOREIGN KEY (`premio_id`)       REFERENCES `premios`        (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pa_participacao` FOREIGN KEY (`participacao_id`) REFERENCES `participacoes`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECÇÃO 10: PARCEIROS E PATROCINADORES
-- =====================================================================

CREATE TABLE `patrocinadores` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome`         VARCHAR(180) NOT NULL,
  `tipo`         ENUM('ministerio','governo_provincial','empresa','ong','escola','biblioteca','clube_leitura','media','outro') NOT NULL,
  `nivel`        ENUM('diamante','ouro','prata','bronze','apoiador','institucional') NULL,
  `logo_url`     VARCHAR(255) NULL,
  `website`      VARCHAR(255) NULL,
  `email`        VARCHAR(120) NULL,
  `telefone`     VARCHAR(30) NULL,
  `contacto_pessoa` VARCHAR(150) NULL,
  `descricao`    TEXT NULL,
  `ativo`        TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   DATETIME NULL,
  `updated_at`   DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `patrocinadores_edicao` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patrocinador_id` INT UNSIGNED NOT NULL,
  `edicao_id`       INT UNSIGNED NOT NULL,
  `nivel_apoio`     ENUM('diamante','ouro','prata','bronze','apoiador','institucional') NOT NULL,
  `valor_contribuido` DECIMAL(15,2) NULL,
  `moeda`           CHAR(3) NOT NULL DEFAULT 'AOA',
  `descricao_apoio` TEXT NULL,
  `created_at`      DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patr_edicao` (`patrocinador_id`, `edicao_id`),
  CONSTRAINT `fk_pe_patrocinador` FOREIGN KEY (`patrocinador_id`) REFERENCES `patrocinadores`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pe_edicao`       FOREIGN KEY (`edicao_id`)       REFERENCES `edicoes_concurso` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adiciona FK de patrocinador em premios (foi definido depois)
ALTER TABLE `premios`
  ADD CONSTRAINT `fk_premio_patrocinador`
  FOREIGN KEY (`patrocinador_id`) REFERENCES `patrocinadores` (`id`) ON DELETE SET NULL;

-- =====================================================================
-- SECÇÃO 11: CAPACITAÇÃO (objectivo 4 do TdR)
-- =====================================================================

CREATE TABLE `capacitacoes` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `edicao_id`      INT UNSIGNED NULL,
  `titulo`         VARCHAR(180) NOT NULL,
  `descricao`      TEXT NULL,
  `publico_alvo`   SET('professores','jurados','pronunciadores','coordenadores','organizadores') NOT NULL,
  `modalidade`     ENUM('presencial','online','hibrida') NOT NULL,
  `local_id`       INT UNSIGNED NULL,
  `link_online`    VARCHAR(255) NULL,
  `data_inicio`    DATETIME NULL,
  `data_fim`       DATETIME NULL,
  `carga_horaria`  INT UNSIGNED NULL,
  `vagas`          INT UNSIGNED NULL,
  `formador_principal` VARCHAR(180) NULL,
  `material_apoio_url` VARCHAR(255) NULL,
  `status`         ENUM('agendada','inscricoes_abertas','em_curso','concluida','cancelada') NOT NULL DEFAULT 'agendada',
  `created_at`     DATETIME NULL,
  `updated_at`     DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_capac_edicao` (`edicao_id`),
  CONSTRAINT `fk_capac_edicao` FOREIGN KEY (`edicao_id`) REFERENCES `edicoes_concurso` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_capac_local`  FOREIGN KEY (`local_id`)  REFERENCES `locais_evento`    (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `participantes_capacitacao` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `capacitacao_id` INT UNSIGNED NOT NULL,
  `user_id`        INT UNSIGNED NOT NULL,
  `presente`       TINYINT(1) NOT NULL DEFAULT 0,
  `concluiu`       TINYINT(1) NOT NULL DEFAULT 0,
  `nota`           DECIMAL(4,2) NULL,
  `certificado_url` VARCHAR(255) NULL,
  `created_at`     DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pc_capac_user` (`capacitacao_id`, `user_id`),
  CONSTRAINT `fk_pc_capac` FOREIGN KEY (`capacitacao_id`) REFERENCES `capacitacoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_user`  FOREIGN KEY (`user_id`)        REFERENCES `users`        (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECÇÃO 12: SISTEMA DE NOTÍCIAS (estilo WordPress)
-- =====================================================================

CREATE TABLE `noticias_categorias` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id`    INT UNSIGNED NULL COMMENT 'Para subcategorias',
  `nome`         VARCHAR(100) NOT NULL,
  `slug`         VARCHAR(120) NOT NULL,
  `descricao`    TEXT NULL,
  `cor`          VARCHAR(7) NULL COMMENT 'Cor HEX para o tema',
  `ordem`        INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`   DATETIME NULL,
  `updated_at`   DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_noticat_slug` (`slug`),
  KEY `idx_noticat_parent` (`parent_id`),
  CONSTRAINT `fk_noticat_parent`
    FOREIGN KEY (`parent_id`) REFERENCES `noticias_categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `noticias_tags` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome`       VARCHAR(80) NOT NULL,
  `slug`       VARCHAR(100) NOT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tag_slug` (`slug`),
  UNIQUE KEY `uq_tag_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_biblioteca` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED NOT NULL COMMENT 'Quem fez upload',
  `nome_arquivo` VARCHAR(255) NOT NULL,
  `nome_original` VARCHAR(255) NOT NULL,
  `caminho`      VARCHAR(500) NOT NULL,
  `url`          VARCHAR(500) NOT NULL,
  `mime_type`    VARCHAR(100) NOT NULL,
  `tipo`         ENUM('imagem','video','audio','documento','outro') NOT NULL,
  `tamanho_bytes` BIGINT UNSIGNED NOT NULL,
  `largura`      INT UNSIGNED NULL,
  `altura`       INT UNSIGNED NULL,
  `duracao_seg`  INT UNSIGNED NULL,
  `titulo`       VARCHAR(255) NULL,
  `descricao`    TEXT NULL,
  `texto_alt`    VARCHAR(255) NULL COMMENT 'Alt text para acessibilidade',
  `legenda`      VARCHAR(500) NULL,
  `created_at`   DATETIME NULL,
  `updated_at`   DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_media_user` (`user_id`),
  KEY `idx_media_tipo` (`tipo`),
  CONSTRAINT `fk_media_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `noticias` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `autor_id`         INT UNSIGNED NOT NULL,
  `editor_id`        INT UNSIGNED NULL COMMENT 'Quem aprovou/publicou',
  `titulo`           VARCHAR(255) NOT NULL,
  `slug`             VARCHAR(280) NOT NULL,
  `subtitulo`        VARCHAR(500) NULL,
  `resumo`           TEXT NULL COMMENT 'Excerpt/lead da notícia',
  `conteudo`         LONGTEXT NOT NULL,
  `imagem_destacada_id` INT UNSIGNED NULL,
  `tipo_post`        ENUM('noticia','artigo','comunicado','reportagem','entrevista','editorial','pagina') NOT NULL DEFAULT 'noticia',
  `formato`          ENUM('padrao','galeria','video','audio','citacao','link') NOT NULL DEFAULT 'padrao',
  `status`           ENUM('rascunho','revisao','agendada','publicada','privada','arquivada','lixeira') NOT NULL DEFAULT 'rascunho',
  `visibilidade`     ENUM('publica','privada','protegida_senha') NOT NULL DEFAULT 'publica',
  `senha`            VARCHAR(255) NULL,
  `permitir_comentarios` TINYINT(1) NOT NULL DEFAULT 1,
  `destaque`         TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Notícia em destaque na home',
  `fixada`           TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Sempre no topo',
  `provincia_id`     INT UNSIGNED NULL COMMENT 'Notícia regional, se aplicável',
  `edicao_id`        INT UNSIGNED NULL COMMENT 'Notícia ligada a uma edição do concurso',
  `evento_id`        INT UNSIGNED NULL COMMENT 'Notícia ligada a um evento específico',
  `meta_titulo`      VARCHAR(255) NULL COMMENT 'SEO title',
  `meta_descricao`   VARCHAR(500) NULL COMMENT 'SEO description',
  `meta_keywords`    VARCHAR(500) NULL,
  `og_imagem`        VARCHAR(500) NULL COMMENT 'Open Graph imagem',
  `visualizacoes`    INT UNSIGNED NOT NULL DEFAULT 0,
  `tempo_leitura_min` INT UNSIGNED NULL,
  `data_publicacao`  DATETIME NULL,
  `data_agendada`    DATETIME NULL,
  `created_at`       DATETIME NULL,
  `updated_at`       DATETIME NULL,
  `deleted_at`       DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_noticia_slug` (`slug`),
  KEY `idx_noticia_autor` (`autor_id`),
  KEY `idx_noticia_status` (`status`),
  KEY `idx_noticia_data_pub` (`data_publicacao`),
  KEY `idx_noticia_destaque` (`destaque`, `fixada`),
  KEY `idx_noticia_provincia` (`provincia_id`),
  KEY `idx_noticia_edicao` (`edicao_id`),
  FULLTEXT KEY `ft_noticia` (`titulo`, `subtitulo`, `resumo`, `conteudo`),
  CONSTRAINT `fk_not_autor`     FOREIGN KEY (`autor_id`)            REFERENCES `users`             (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_not_editor`    FOREIGN KEY (`editor_id`)           REFERENCES `users`             (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_not_imagem`    FOREIGN KEY (`imagem_destacada_id`) REFERENCES `media_biblioteca`  (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_not_provincia` FOREIGN KEY (`provincia_id`)        REFERENCES `provincias`        (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_not_edicao`    FOREIGN KEY (`edicao_id`)           REFERENCES `edicoes_concurso`  (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_not_evento`    FOREIGN KEY (`evento_id`)           REFERENCES `eventos_competicao`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `noticias_categorias_rel` (
  `noticia_id`   INT UNSIGNED NOT NULL,
  `categoria_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`noticia_id`, `categoria_id`),
  KEY `idx_ncr_categoria` (`categoria_id`),
  CONSTRAINT `fk_ncr_noticia`   FOREIGN KEY (`noticia_id`)   REFERENCES `noticias`            (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ncr_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `noticias_categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `noticias_tags_rel` (
  `noticia_id` INT UNSIGNED NOT NULL,
  `tag_id`     INT UNSIGNED NOT NULL,
  PRIMARY KEY (`noticia_id`, `tag_id`),
  KEY `idx_ntr_tag` (`tag_id`),
  CONSTRAINT `fk_ntr_noticia` FOREIGN KEY (`noticia_id`) REFERENCES `noticias`        (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ntr_tag`     FOREIGN KEY (`tag_id`)     REFERENCES `noticias_tags`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sistema de revisões (histórico de edições, estilo WordPress)
CREATE TABLE `noticias_revisoes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `noticia_id` INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `titulo`     VARCHAR(255) NOT NULL,
  `conteudo`   LONGTEXT NOT NULL,
  `resumo`     TEXT NULL,
  `motivo`     VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rev_noticia` (`noticia_id`),
  KEY `idx_rev_user` (`user_id`),
  CONSTRAINT `fk_rev_noticia` FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentários (com estrutura threaded — respostas a comentários)
CREATE TABLE `noticias_comentarios` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `noticia_id` INT UNSIGNED NOT NULL,
  `parent_id`  INT UNSIGNED NULL COMMENT 'Para respostas',
  `user_id`    INT UNSIGNED NULL COMMENT 'NULL se comentário de visitante',
  `nome_autor` VARCHAR(100) NULL COMMENT 'Para visitantes não registados',
  `email_autor` VARCHAR(120) NULL,
  `website_autor` VARCHAR(255) NULL,
  `ip_autor`   VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `conteudo`   TEXT NOT NULL,
  `status`     ENUM('pendente','aprovado','spam','lixeira') NOT NULL DEFAULT 'pendente',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_com_noticia` (`noticia_id`),
  KEY `idx_com_parent` (`parent_id`),
  KEY `idx_com_user` (`user_id`),
  KEY `idx_com_status` (`status`),
  CONSTRAINT `fk_com_noticia` FOREIGN KEY (`noticia_id`) REFERENCES `noticias`             (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_com_parent`  FOREIGN KEY (`parent_id`)  REFERENCES `noticias_comentarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_com_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`                (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECÇÃO 13: PÁGINAS DINÂMICAS (sobre, regulamento, contactos...)
-- =====================================================================

CREATE TABLE `paginas` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id`       INT UNSIGNED NULL,
  `autor_id`        INT UNSIGNED NOT NULL,
  `titulo`          VARCHAR(255) NOT NULL,
  `slug`            VARCHAR(280) NOT NULL,
  `conteudo`        LONGTEXT NOT NULL,
  `template`        VARCHAR(80) NULL COMMENT 'Template específico, se houver',
  `ordem`           INT UNSIGNED NOT NULL DEFAULT 0,
  `status`          ENUM('rascunho','publicada','privada','arquivada') NOT NULL DEFAULT 'rascunho',
  `meta_titulo`     VARCHAR(255) NULL,
  `meta_descricao`  VARCHAR(500) NULL,
  `mostra_no_menu`  TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`      DATETIME NULL,
  `updated_at`      DATETIME NULL,
  `deleted_at`      DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pagina_slug` (`slug`),
  KEY `idx_pagina_parent` (`parent_id`),
  KEY `idx_pagina_status` (`status`),
  CONSTRAINT `fk_pag_parent` FOREIGN KEY (`parent_id`) REFERENCES `paginas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pag_autor`  FOREIGN KEY (`autor_id`)  REFERENCES `users`   (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menus` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome`       VARCHAR(80) NOT NULL,
  `localizacao` VARCHAR(50) NOT NULL COMMENT 'header, footer, sidebar...',
  `ativo`      TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menus_itens` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `menu_id`    INT UNSIGNED NOT NULL,
  `parent_id`  INT UNSIGNED NULL,
  `tipo`       ENUM('pagina','noticia','categoria','url_externa','custom') NOT NULL,
  `pagina_id`  INT UNSIGNED NULL,
  `noticia_id` INT UNSIGNED NULL COMMENT 'Preenchido quando tipo = noticia',
  `categoria_id` INT UNSIGNED NULL,
  `label`      VARCHAR(120) NOT NULL,
  `url`        VARCHAR(500) NULL,
  `target`     VARCHAR(20) NOT NULL DEFAULT '_self',
  `icone`      VARCHAR(80) NULL,
  `ordem`      INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_mi_menu` (`menu_id`),
  KEY `idx_mi_parent` (`parent_id`),
  CONSTRAINT `fk_mi_menu`      FOREIGN KEY (`menu_id`)      REFERENCES `menus`               (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mi_parent`    FOREIGN KEY (`parent_id`)    REFERENCES `menus_itens`         (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mi_pagina`    FOREIGN KEY (`pagina_id`)    REFERENCES `paginas`             (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mi_noticia`   FOREIGN KEY (`noticia_id`)   REFERENCES `noticias`            (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mi_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `noticias_categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECÇÃO 14: NEWSLETTER E NOTIFICAÇÕES
-- =====================================================================

CREATE TABLE `newsletter_subscritores` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`           VARCHAR(150) NOT NULL,
  `nome`            VARCHAR(120) NULL,
  `provincia_id`    INT UNSIGNED NULL,
  `confirmado`      TINYINT(1) NOT NULL DEFAULT 0,
  `token_confirmacao` VARCHAR(100) NULL,
  `data_confirmacao` DATETIME NULL,
  `cancelou`        TINYINT(1) NOT NULL DEFAULT 0,
  `data_cancelamento` DATETIME NULL,
  `ip_inscricao`    VARCHAR(45) NULL,
  `created_at`      DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_news_email` (`email`),
  CONSTRAINT `fk_news_provincia` FOREIGN KEY (`provincia_id`) REFERENCES `provincias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notificações internas (sino no topo da aplicação)
CREATE TABLE `notificacoes` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `tipo`       VARCHAR(60) NOT NULL COMMENT 'inscricao_validada, evento_proximo, resultado, etc',
  `titulo`     VARCHAR(180) NOT NULL,
  `mensagem`   TEXT NOT NULL,
  `link`       VARCHAR(500) NULL,
  `lida`       TINYINT(1) NOT NULL DEFAULT 0,
  `lida_em`    DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user_lida` (`user_id`, `lida`),
  KEY `idx_notif_tipo` (`tipo`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Templates reutilizáveis de notificação (sistema, e-mail e SMS)
-- Os corpos suportam placeholders no formato {{nome_variavel}},
-- substituídos pelo NotificacaoService na aplicação.
CREATE TABLE `notificacoes_templates` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo`     VARCHAR(80) NOT NULL COMMENT 'Chave única usada pelo código, ex: inscricao_validada_sms',
  `nome`       VARCHAR(150) NOT NULL,
  `canal`      ENUM('sistema','email','sms') NOT NULL,
  `assunto`    VARCHAR(255) NULL COMMENT 'Apenas para e-mail',
  `corpo`      TEXT NOT NULL COMMENT 'Suporta placeholders {{candidato_nome}}, {{edicao}}, etc',
  `descricao`  VARCHAR(255) NULL,
  `ativo`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_template_codigo` (`codigo`),
  KEY `idx_template_canal` (`canal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fila de envio multi-canal com controlo de retries.
-- Processada por comando CLI (php spark notificacoes:processar)
-- agendado via cron. Garante que falhas temporárias do provedor
-- (SMTP ou pro2sms) não perdem mensagens.
CREATE TABLE `notificacoes_fila` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `canal`                ENUM('email','sms','sistema') NOT NULL,
  `user_id`              INT UNSIGNED NULL COMMENT 'Destinatário interno, se aplicável',
  `destinatario`         VARCHAR(180) NOT NULL COMMENT 'E-mail ou número de telefone',
  `template_id`          INT UNSIGNED NULL,
  `assunto`              VARCHAR(255) NULL,
  `corpo`                TEXT NOT NULL COMMENT 'Corpo já renderizado (placeholders substituídos)',
  `dados_json`           JSON NULL COMMENT 'Dados de contexto usados na renderização',
  `prioridade`           TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '1 = mais urgente, 9 = menos urgente',
  `status`               ENUM('pendente','a_enviar','enviada','falhada','cancelada') NOT NULL DEFAULT 'pendente',
  `tentativas`           TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `max_tentativas`       TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `proxima_tentativa_em` DATETIME NULL COMMENT 'Backoff exponencial gerido pela aplicação',
  `enviada_em`           DATETIME NULL,
  `erro_ultimo`          TEXT NULL,
  `created_at`           DATETIME NULL,
  `updated_at`           DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fila_processamento` (`status`, `proxima_tentativa_em`, `prioridade`),
  KEY `idx_fila_user` (`user_id`),
  KEY `idx_fila_canal` (`canal`),
  CONSTRAINT `fk_fila_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`                  (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fila_template` FOREIGN KEY (`template_id`) REFERENCES `notificacoes_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log de e-mails enviados (auditoria + diagnóstico de entregabilidade)
CREATE TABLE `logs_email` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fila_id`         BIGINT UNSIGNED NULL COMMENT 'Origem na fila, se veio de lá',
  `user_id`         INT UNSIGNED NULL,
  `destinatario`    VARCHAR(180) NOT NULL,
  `assunto`         VARCHAR(255) NOT NULL,
  `template_codigo` VARCHAR(80) NULL,
  `provider`        VARCHAR(60) NOT NULL DEFAULT 'smtp' COMMENT 'smtp, sendgrid, ses...',
  `message_id`      VARCHAR(255) NULL COMMENT 'ID devolvido pelo servidor de e-mail',
  `status`          ENUM('enviado','falhado','devolvido') NOT NULL,
  `erro`            TEXT NULL,
  `created_at`      DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lemail_destinatario` (`destinatario`),
  KEY `idx_lemail_status` (`status`),
  KEY `idx_lemail_data` (`created_at`),
  CONSTRAINT `fk_lemail_fila` FOREIGN KEY (`fila_id`) REFERENCES `notificacoes_fila` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lemail_user` FOREIGN KEY (`user_id`) REFERENCES `users`             (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log de SMS enviados via pro2sms.ao (auditoria + controlo de custos)
CREATE TABLE `logs_sms` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fila_id`             BIGINT UNSIGNED NULL,
  `user_id`             INT UNSIGNED NULL,
  `telefone`            VARCHAR(30) NOT NULL COMMENT 'Formato E.164, ex: +244923000000',
  `mensagem`            VARCHAR(500) NOT NULL,
  `partes`              TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Número de segmentos SMS (160 chars GSM-7)',
  `provider`            VARCHAR(60) NOT NULL DEFAULT 'pro2sms',
  `provider_message_id` VARCHAR(120) NULL COMMENT 'ID devolvido pela API pro2sms',
  `status`              ENUM('enviado','entregue','falhado','expirado') NOT NULL,
  `custo`               DECIMAL(10,4) NULL COMMENT 'Custo reportado pelo provedor',
  `moeda`               CHAR(3) NOT NULL DEFAULT 'AOA',
  `resposta_api`        JSON NULL COMMENT 'Resposta bruta da API para diagnóstico',
  `erro`                TEXT NULL,
  `created_at`          DATETIME NOT NULL,
  `updated_at`          DATETIME NULL COMMENT 'Atualizado ao receber callback de entrega',
  PRIMARY KEY (`id`),
  KEY `idx_lsms_telefone` (`telefone`),
  KEY `idx_lsms_status` (`status`),
  KEY `idx_lsms_data` (`created_at`),
  KEY `idx_lsms_provider_msg` (`provider_message_id`),
  CONSTRAINT `fk_lsms_fila` FOREIGN KEY (`fila_id`) REFERENCES `notificacoes_fila` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lsms_user` FOREIGN KEY (`user_id`) REFERENCES `users`             (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECÇÃO 15: CONFIGURAÇÕES E AUDITORIA
-- =====================================================================
DROP TABLE IF EXISTS `configuracoes`;
CREATE TABLE `configuracoes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `chave`      VARCHAR(100) NOT NULL,
  `valor`      TEXT NULL,
  `tipo`       ENUM('string','integer','boolean','json','text') NOT NULL DEFAULT 'string',
  `grupo`      VARCHAR(60) NOT NULL DEFAULT 'geral',
  `descricao`  VARCHAR(255) NULL,
  `editavel`   TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY uq_config_chave (chave),
  KEY `idx_config_grupo` (`grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `auditoria_logs` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED NULL,
  `acao`          VARCHAR(80) NOT NULL COMMENT 'login, logout, criar, editar, eliminar...',
  `entidade`      VARCHAR(80) NOT NULL COMMENT 'Tabela/recurso afetado',
  `entidade_id`   BIGINT UNSIGNED NULL,
  `descricao`     TEXT NULL,
  `dados_antes`   JSON NULL,
  `dados_depois`  JSON NULL,
  `ip_address`    VARCHAR(45) NULL,
  `user_agent`    VARCHAR(255) NULL,
  `created_at`    DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_entidade` (`entidade`, `entidade_id`),
  KEY `idx_audit_data` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECÇÃO 16: VIEWS ÚTEIS
-- =====================================================================

-- Ranking actual por edição/categoria/província
CREATE OR REPLACE VIEW `v_ranking_provincial` AS
SELECT
  p.id AS provincia_id,
  p.nome AS provincia,
  e.id AS edicao_id,
  e.ano AS edicao_ano,
  cat.id AS categoria_id,
  cat.nome AS categoria,
  c.id AS candidato_id,
  c.nome_completo,
  c.classe_atual,
  esc.nome AS escola,
  pa.posicao_final,
  pa.pontuacao_total
FROM participacoes pa
JOIN inscricoes i ON i.id = pa.inscricao_id
JOIN candidatos c ON c.id = i.candidato_id
JOIN escolas esc ON esc.id = i.escola_id
JOIN provincias p ON p.id = i.provincia_id
JOIN edicoes_concurso e ON e.id = i.edicao_id
JOIN categorias_competicao cat ON cat.id = i.categoria_id
WHERE pa.posicao_final IS NOT NULL
ORDER BY p.nome, e.ano DESC, cat.ordem, pa.posicao_final;

-- Estatísticas por província
CREATE OR REPLACE VIEW `v_estatisticas_provincias` AS
SELECT
  p.id AS provincia_id,
  p.nome AS provincia,
  COUNT(DISTINCT esc.id) AS total_escolas,
  COUNT(DISTINCT c.id) AS total_candidatos,
  COUNT(DISTINCT i.id) AS total_inscricoes,
  SUM(CASE WHEN i.status = 'validada' THEN 1 ELSE 0 END) AS inscricoes_validadas
FROM provincias p
LEFT JOIN escolas esc ON esc.provincia_id = p.id
LEFT JOIN candidatos c ON c.provincia_id = p.id
LEFT JOIN inscricoes i ON i.candidato_id = c.id
GROUP BY p.id, p.nome;

-- Histórico de uso de palavras em concursos (requisito: banco de
-- palavras com histórico de uso)
CREATE OR REPLACE VIEW `v_historico_uso_palavras` AS
SELECT
  w.id            AS palavra_id,
  w.palavra,
  w.dificuldade,
  t.id            AS tentativa_id,
  t.correta,
  t.tempo_resposta_seg,
  r.numero_round,
  ev.id           AS evento_id,
  ev.nome         AS evento,
  ev.data_evento,
  f.tipo_fase,
  ed.ano          AS edicao_ano
FROM tentativas_soletracao t
JOIN palavras w            ON w.id  = t.palavra_id
JOIN rounds_evento r       ON r.id  = t.round_id
JOIN eventos_competicao ev ON ev.id = r.evento_id
JOIN fases_concurso f      ON f.id  = ev.fase_id
JOIN edicoes_concurso ed   ON ed.id = f.edicao_id;

-- =====================================================================
-- SECÇÃO 17: TRIGGERS PARA INTEGRIDADE DE REGRAS DE NEGÓCIO
-- =====================================================================

DELIMITER $$

-- Garantir que candidato só concorre na província onde se inscreveu
CREATE TRIGGER `trg_inscricao_provincia_check`
BEFORE INSERT ON `inscricoes`
FOR EACH ROW
BEGIN
  DECLARE v_provincia_candidato INT UNSIGNED;
  SELECT provincia_id INTO v_provincia_candidato
  FROM candidatos WHERE id = NEW.candidato_id;

  IF v_provincia_candidato IS NOT NULL AND v_provincia_candidato <> NEW.provincia_id THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'O candidato só pode concorrer na província onde está registado.';
  END IF;
END$$

-- Impedir alteração de província após inscrição validada
CREATE TRIGGER `trg_candidato_provincia_imutavel`
BEFORE UPDATE ON `candidatos`
FOR EACH ROW
BEGIN
  DECLARE v_inscricoes_validadas INT;
  IF OLD.provincia_id <> NEW.provincia_id THEN
    SELECT COUNT(*) INTO v_inscricoes_validadas
    FROM inscricoes
    WHERE candidato_id = OLD.id AND status = 'validada';

    IF v_inscricoes_validadas > 0 THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Não é possível alterar a província: candidato já tem inscrições validadas.';
    END IF;
  END IF;
END$$

-- NOTA: a geração de slug foi retirada dos triggers.
-- Uma transliteração correcta do português (á, à, â, ã, é, ê, í, ó,
-- ô, õ, ú, ü, ç, hífenes duplicados, colisões de slug...) não é
-- viável em SQL puro. O slug é gerado pelo helper url_title() +
-- serviço SlugService no CodeIgniter 4, que também garante unicidade
-- acrescentando sufixo numérico em caso de colisão.

-- Registar revisão automaticamente quando notícia é actualizada
CREATE TRIGGER `trg_noticias_revisao`
BEFORE UPDATE ON `noticias`
FOR EACH ROW
BEGIN
  IF OLD.conteudo <> NEW.conteudo OR OLD.titulo <> NEW.titulo THEN
    INSERT INTO noticias_revisoes (noticia_id, user_id, titulo, conteudo, resumo, created_at)
    VALUES (OLD.id, COALESCE(NEW.editor_id, NEW.autor_id), OLD.titulo, OLD.conteudo, OLD.resumo, NOW());
  END IF;
END$$

-- Actualizar estatísticas da palavra e marcar o pool quando uma
-- palavra é efectivamente usada num round
CREATE TRIGGER `trg_tentativa_estatistica_palavra`
AFTER INSERT ON `tentativas_soletracao`
FOR EACH ROW
BEGIN
  -- Incrementa o contador global de uso da palavra
  UPDATE palavras
     SET usada_em_concursos = usada_em_concursos + 1
   WHERE id = NEW.palavra_id;

  -- Marca a palavra como usada no pool do evento correspondente
  UPDATE pool_palavras_evento ppe
    JOIN rounds_evento r ON r.id = NEW.round_id
     SET ppe.usada = 1
   WHERE ppe.palavra_id = NEW.palavra_id
     AND ppe.evento_id  = r.evento_id;
END$$

DELIMITER ;

-- =====================================================================
-- SECÇÃO 18: DADOS INICIAIS (SEED)
-- =====================================================================

-- As 21 províncias de Angola (Lei da Divisão Político-Administrativa de 2024)
INSERT INTO `provincias` (`nome`, `codigo`, `capital`, `regiao`, `ativo`, `created_at`) VALUES
('Bengo',         'BGO', 'Caxito',         'Norte',   1, NOW()),
('Benguela',      'BGU', 'Benguela',       'Oeste',   1, NOW()),
('Bié',           'BIE', 'Cuíto',          'Centro',  1, NOW()),
('Cabinda',       'CAB', 'Cabinda',        'Norte',   1, NOW()),
('Cuando',        'CDO', 'Mavinga',        'Leste',   1, NOW()),
('Cubango',       'CBG', 'Menongue',       'Sul',     1, NOW()),
('Cuanza Norte',  'CNO', 'N''dalatando',   'Norte',   1, NOW()),
('Cuanza Sul',    'CSU', 'Sumbe',          'Centro',  1, NOW()),
('Cunene',        'CNN', 'Ondjiva',        'Sul',     1, NOW()),
('Huambo',        'HMB', 'Huambo',         'Centro',  1, NOW()),
('Huíla',         'HLA', 'Lubango',        'Sul',     1, NOW()),
('Icolo e Bengo', 'ICB', 'Catete',         'Norte',   1, NOW()),
('Luanda',        'LDA', 'Luanda',         'Capital', 1, NOW()),
('Lunda Norte',   'LNO', 'Dundo',          'Leste',   1, NOW()),
('Lunda Sul',     'LSU', 'Saurimo',        'Leste',   1, NOW()),
('Malanje',       'MAL', 'Malanje',        'Norte',   1, NOW()),
('Moxico',        'MOX', 'Luena',          'Leste',   1, NOW()),
('Moxico Leste',  'MXL', 'Cazombo',        'Leste',   1, NOW()),
('Namibe',        'NMB', 'Moçâmedes',      'Sul',     1, NOW()),
('Uíge',          'UIG', 'Uíge',           'Norte',   1, NOW()),
('Zaire',         'ZAI', 'M''banza Kongo', 'Norte',   1, NOW());

-- Categorias iniciais de palavras
INSERT INTO `palavras_categorias` (`nome`, `descricao`, `created_at`) VALUES
('Cultura Angolana',     'Palavras ligadas à cultura, geografia e história de Angola', NOW()),
('Ciência',              'Termos científicos e técnicos',                              NOW()),
('Literatura',           'Vocabulário literário e poético',                            NOW()),
('Língua Portuguesa',    'Vocabulário geral da norma padrão',                          NOW()),
('Geografia',            'Termos geográficos',                                         NOW()),
('História',             'Termos históricos',                                          NOW()),
('Matemática',           'Vocabulário matemático',                                     NOW()),
('Estrangeirismos',      'Palavras de origem estrangeira incorporadas',                NOW());

-- Categorias iniciais de notícias
INSERT INTO `noticias_categorias` (`nome`, `slug`, `descricao`, `cor`, `ordem`, `created_at`) VALUES
('Notícias Gerais',     'noticias-gerais',     'Notícias gerais sobre o concurso',                  '#1e40af', 1, NOW()),
('Resultados',          'resultados',          'Resultados das fases do concurso',                  '#16a34a', 2, NOW()),
('Eventos',             'eventos',             'Eventos e cerimónias',                              '#f59e0b', 3, NOW()),
('Capacitação',         'capacitacao',         'Formações de professores e jurados',                '#7c3aed', 4, NOW()),
('Parcerias',           'parcerias',           'Anúncios de novas parcerias e patrocínios',         '#0891b2', 5, NOW()),
('Histórias de Sucesso','historias-sucesso',   'Histórias dos candidatos e suas trajectórias',      '#dc2626', 6, NOW()),
('Comunicados Oficiais','comunicados',         'Comunicados oficiais da organização',               '#374151', 7, NOW());

-- Configurações iniciais
INSERT INTO `configuracoes` (`chave`, `valor`, `tipo`, `grupo`, `descricao`, `updated_at`) VALUES
('site_nome',              'Concurso Nacional de Soletração - Angola', 'string',  'geral',     'Nome oficial do site',           NOW()),
('site_descricao',         'Plataforma oficial do concurso',          'string',  'geral',     'Descrição curta',                NOW()),
('site_email',             'contacto@soletracao.ao',                  'string',  'contactos', 'Email de contacto',              NOW()),
('site_telefone',          '+244 000 000 000',                        'string',  'contactos', 'Telefone de contacto',           NOW()),
('inscricoes_abertas',     '0',                                       'boolean', 'concurso',  'Se as inscrições estão abertas', NOW()),
('edicao_ativa_id',        '',                                        'integer', 'concurso',  'ID da edição activa',            NOW()),
('comentarios_moderar',    '1',                                       'boolean', 'noticias',  'Moderar todos os comentários',   NOW()),
('newsletter_ativa',       '1',                                       'boolean', 'noticias',  'Permitir subscrição newsletter', NOW()),
('idiomas_disponiveis',    'pt-AO,pt-PT',                            'string',  'geral',     'Idiomas do site',                NOW()),
('timezone',               'Africa/Luanda',                           'string',  'geral',     'Fuso horário de apresentação (armazenamento é sempre UTC)', NOW()),
('email_remetente',        'nao-responder@soletracao.ao',             'string',  'notificacoes', 'Endereço remetente dos e-mails',  NOW()),
('email_remetente_nome',   'Concurso Nacional de Soletração',         'string',  'notificacoes', 'Nome apresentado no remetente',   NOW()),
('sms_provider',           'pro2sms',                                 'string',  'notificacoes', 'Provedor de SMS activo',          NOW()),
('sms_remetente',          'SOLETRACAO',                              'string',  'notificacoes', 'Sender ID dos SMS',               NOW()),
('sms_ativo',              '1',                                       'boolean', 'notificacoes', 'Envio de SMS activado',           NOW()),
('fila_max_tentativas',    '3',                                       'integer', 'notificacoes', 'Máximo de retries por mensagem',  NOW());

-- Templates iniciais de notificação
INSERT INTO `notificacoes_templates` (`codigo`, `nome`, `canal`, `assunto`, `corpo`, `descricao`, `ativo`, `created_at`) VALUES
('inscricao_recebida_email', 'Inscrição recebida (e-mail)', 'email',
 'Inscrição recebida — {{edicao_nome}}',
 'Caro(a) {{encarregado_nome}},\n\nA inscrição de {{candidato_nome}} no {{edicao_nome}} foi recebida com o número {{numero_inscricao}} e aguarda validação.\n\nAcompanhe o estado em {{link_acompanhamento}}.',
 'Enviado ao encarregado quando a inscrição é submetida', 1, NOW()),
('inscricao_validada_sms', 'Inscrição validada (SMS)', 'sms', NULL,
 'CNS Angola: a inscricao de {{candidato_nome}} ({{numero_inscricao}}) foi VALIDADA. Provincia: {{provincia}}.',
 'SMS ao encarregado quando a inscrição é validada', 1, NOW()),
('evento_convocatoria_sms', 'Convocatória para evento (SMS)', 'sms', NULL,
 'CNS Angola: {{candidato_nome}} esta convocado(a) para {{evento_nome}} em {{data_evento}}, {{local}}. Chegar 1h antes.',
 'SMS de convocatória para eventos de competição', 1, NOW()),
('resultado_publicado_email', 'Resultado publicado (e-mail)', 'email',
 'Resultados disponíveis — {{evento_nome}}',
 'Os resultados de {{evento_nome}} já estão disponíveis.\n\n{{candidato_nome}} classificou-se em {{posicao}}.º lugar.\n\nConsulte os detalhes em {{link_resultados}}.',
 'Enviado quando os resultados de um evento são homologados', 1, NOW());

-- [migration] removido: SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- FIM DO SCRIPT — VERSÃO 2.0
-- =====================================================================
-- TOTAL: 53 tabelas, 3 views, 4 triggers
-- Novidades v2.0: progressoes_fase, notificacoes_templates,
-- notificacoes_fila, logs_email, logs_sms, uuid públicos,
-- correcções de integridade e seeds de notificações.
-- Estrutura preparada para CodeIgniter 4 + Shield.
-- No projecto CI4, este esquema deve ser convertido em MIGRATIONS
-- (uma por secção), mantendo este ficheiro como referência.
-- =====================================================================
