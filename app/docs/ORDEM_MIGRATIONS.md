# Ordem canónica das migrations

As migrations devem correr na ordem das dependências de chave estrangeira.
Cada linha = uma migration (uma por secção do SQL v2.0). O timestamp no
nome do ficheiro garante a ordem; abaixo a sequência recomendada.

| # | Migration | Tabelas | Depende de |
|---|---|---|---|
| 100 | CriarGeografia | provincias, municipios | — |
| 200 | *(Shield)* | users, auth_* | — (php spark shield:setup) |
| 300 | CriarEscolas | escolas | provincias, municipios |
| 400 | CriarPerfisUtilizador | perfis_utilizador, coordenadores_atribuicao | users, provincias, escolas |
| 500 | CriarEdicoes | edicoes_concurso, categorias_competicao, fases_concurso | users |
| 600 | CriarLocaisEEventos | locais_evento, eventos_competicao | fases, categorias, escolas |
| 700 | CriarCandidatos | candidatos, encarregados_educacao | escolas, users |
| 800 | CriarInscricoes | inscricoes, participacoes | candidatos, edicoes, categorias |
| 900 | CriarJuri | juri_evento | eventos, users |
| 1000 | CriarPalavras | categorias_palavras, palavras | users |
| 1100 | CriarRoundsETentativas | rounds_evento, tentativas_soletracao, pool_palavras_evento | eventos, participacoes, palavras |
| 1500 | CriarProgressoesFase | progressoes_fase | inscricoes, fases, eventos, users |
| 1600 | CriarPremiosEPatrocinadores | premios, premios_atribuidos, patrocinadores | edicoes, participacoes |
| 1700 | CriarCapacitacoes | capacitacoes, capacitacoes_participantes | users, provincias |
| 1800 | CriarCms | noticias, noticias_categorias(+rel), noticias_tags(+rel), noticias_revisoes, noticias_comentarios | users, provincias, edicoes, eventos |
| 1900 | CriarMediaEPaginas | media_biblioteca, paginas, menus, menus_itens | users |
| 2000 | CriarNewsletter | subscritores_newsletter | — |
| 2100 | CriarNotificacoes | notificacoes, notificacoes_templates, notificacoes_fila, logs_email, logs_sms | users |
| 2200 | CriarSistema | configuracoes, auditoria_logs | users |
| 9000 | CriarViewsETriggers | v_ranking_provincial, v_historico_uso_palavras, triggers | (todas) |

Seeders (na ordem): ProvinciasSeeder → GruposEPermissoesSeeder (Shield) →
CategoriasPalavrasSeeder → CategoriasNoticiasSeeder → ConfiguracoesSeeder →
TemplatesNotificacaoSeeder → SuperadminSeeder.

Comandos:
```bash
php spark shield:setup           # tabelas de autenticação
php spark migrate                # todas as migrations por ordem
php spark db:seed ProvinciasSeeder
php spark db:seed InicialSeeder  # seeder orquestrador que chama os demais
```

Nota: as VIEWS e TRIGGERS não têm suporte nativo no Forge; a migration
9000 executa o SQL diretamente com `$this->db->query(...)`, copiando os
blocos correspondentes do ficheiro de referência v2.0.
