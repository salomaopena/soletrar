<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

/**
 * Grupos e permissões do Shield (matriz da Fase 3).
 */
class AuthGroups extends ShieldAuthGroups
{
    public string $defaultGroup = 'candidato';

    public array $groups = [
        'superadmin' => ['title' => 'Superadministrador', 'description' => 'Acesso total'],
        'coord_nacional' => ['title' => 'Coordenador Nacional', 'description' => 'Gestão nacional do concurso'],
        'coord_provincial' => ['title' => 'Coordenador Provincial', 'description' => 'Gestão de uma província'],
        'coord_municipal' => ['title' => 'Coordenador Municipal', 'description' => 'Gestão de um município'],
        'coord_escolar' => ['title' => 'Coordenador Escolar', 'description' => 'Gestão de uma escola'],
        'professor' => ['title' => 'Professor Responsável', 'description' => 'Apoio à inscrição'],
        'jurado' => ['title' => 'Jurado', 'description' => 'Avaliação em eventos'],
        'pronunciador' => ['title' => 'Pronunciador', 'description' => 'Leitura de palavras'],
        'encarregado' => ['title' => 'Encarregado de Educação', 'description' => 'Acompanhamento de candidatos'],
        'candidato' => ['title' => 'Candidato', 'description' => 'Aluno concorrente'],
        'editor_noticias' => ['title' => 'Editor de Notícias', 'description' => 'Gestão editorial'],
        'jornalista' => ['title' => 'Jornalista', 'description' => 'Redação de conteúdos'],
    ];

    public array $permissions = [
        'concurso.edicoes.gerir' => 'Criar e configurar edições, fases e categorias',
        'concurso.eventos.gerir' => 'Criar e conduzir eventos e rounds',
        'concurso.resultados.homologar' => 'Homologar e publicar resultados',
        'concurso.juri.avaliar' => 'Avaliar tentativas e decidir apelações',
        'inscricoes.criar' => 'Inscrever candidatos',
        'inscricoes.validar' => 'Validar ou rejeitar inscrições',
        'palavras.gerir' => 'Criar e editar palavras',
        'palavras.validar' => 'Validar palavras para uso em concurso',
        'cms.conteudo.criar' => 'Criar rascunhos de notícias e páginas',
        'cms.conteudo.publicar' => 'Aprovar, agendar e publicar conteúdos',
        'cms.comentarios.moderar' => 'Moderar comentários',
        'cms.media.gerir' => 'Gerir a biblioteca de media',
        'sistema.utilizadores.gerir' => 'Gerir utilizadores e grupos',
        'sistema.configuracoes.gerir' => 'Alterar configurações globais',
        'sistema.auditoria.ver' => 'Consultar registos de auditoria',
    ];

    /**
     * Permissões atribuídas por grupo. O '*' final expande para todas as
     * permissões de um prefixo (ex.: 'concurso.*').
     */
    public array $matrix = [
        'superadmin' => [
            'concurso.*',
            'inscricoes.*',
            'palavras.*',
            'cms.*',
            'sistema.*',
        ],
        'coord_nacional' => [
            'concurso.edicoes.gerir',
            'concurso.eventos.gerir',
            'concurso.resultados.homologar',
            'inscricoes.criar',
            'inscricoes.validar',
            'palavras.gerir',
            'palavras.validar',
            'cms.conteudo.publicar',
            'sistema.auditoria.ver',
        ],
        'coord_provincial' => [
            'concurso.eventos.gerir',
            'concurso.resultados.homologar',
            'inscricoes.criar',
            'inscricoes.validar',
        ],
        'coord_municipal' => ['inscricoes.criar', 'inscricoes.validar'],
        'coord_escolar' => ['inscricoes.criar'],
        'professor' => ['inscricoes.criar'],
        'jurado' => ['concurso.juri.avaliar'],
        'pronunciador' => ['concurso.juri.avaliar'],
        'editor_noticias' => [
            'cms.conteudo.criar',
            'cms.conteudo.publicar',
            'cms.comentarios.moderar',
            'cms.media.gerir',
        ],
        'jornalista' => ['cms.conteudo.criar', 'cms.media.gerir'],
    ];
}
