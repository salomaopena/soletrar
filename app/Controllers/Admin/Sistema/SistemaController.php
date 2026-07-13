<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Sistema;

use App\Controllers\Admin\AdminBaseController;

/**
 * Página de CONFIGURAÇÕES — porta de entrada, em estilo dashboard, para
 * tudo o que não se usa no dia-a-dia (e que por isso saiu da sidebar).
 *
 * Cada cartão é escondido se o utilizador não tiver a permissão.
 */
class SistemaController extends AdminBaseController
{
    public function index()
    {
        $u = auth()->user();

        // [grupo => [ [rota, ícone, título, descrição, permissão], ... ]]
        $secoes = [
            'Estrutura do concurso' => [
                ['admin/edicoes',    'bi-calendar-event',  'Edições',    'Anos do concurso, prazos e estado das inscrições.', 'concurso.edicoes.gerir'],
                ['admin/categorias', 'bi-diagram-3',       'Categorias', 'Faixas por classe e idade de cada edição.',          'concurso.edicoes.gerir'],
                ['admin/fases',      'bi-signpost-split',  'Fases',      'Escolar, provincial e nacional; vagas de apuramento.', 'concurso.edicoes.gerir'],
                ['admin/locais',     'bi-pin-map',         'Locais',     'Espaços onde decorrem os eventos.',                  'concurso.eventos.gerir'],
                ['admin/palavras/categorias', 'bi-bookmarks', 'Categorias de palavras', 'Agrupamento temático do banco de palavras.', 'palavras.gerir'],
            ],

            'Território e pessoas' => [
                ['admin/geografia/escolas',    'bi-building', 'Escolas',      'Escolas públicas, privadas e comparticipadas.', 'sistema.utilizadores.gerir'],
                ['admin/geografia/municipios', 'bi-geo-alt',  'Municípios',   'Divisão administrativa por província.',         'sistema.utilizadores.gerir'],
                ['admin/sistema/utilizadores', 'bi-people',   'Utilizadores', 'Contas, grupos e âmbito territorial.',          'sistema.utilizadores.gerir'],
            ],

            'Conteúdos do portal' => [
                ['admin/cms/paginas',     'bi-file-earmark-text', 'Páginas',    'Páginas institucionais (Sobre, Regulamento…).', 'cms.conteudo.criar'],
                ['admin/cms/menus',       'bi-list-nested',       'Menus',      'Navegação do cabeçalho e do rodapé.',           'cms.conteudo.publicar'],
                ['admin/cms/categorias',  'bi-tags',              'Categorias de notícias', 'Organização editorial.',            'cms.conteudo.criar'],
                ['admin/cms/comentarios', 'bi-chat-left-text',    'Comentários','Moderação de comentários do público.',          'cms.comentarios.moderar'],
            ],

            'Parcerias e formação' => [
                ['admin/parcerias/patrocinadores', 'bi-people-fill', 'Patrocinadores', 'Parceiros institucionais e empresas.', 'sistema.configuracoes.gerir'],
                ['admin/parcerias/premios',        'bi-award',       'Prémios',        'Prémios por posição e categoria.',      'sistema.configuracoes.gerir'],
                ['admin/capacitacoes',             'bi-mortarboard', 'Capacitações',   'Formação de professores e jurados.',    'sistema.configuracoes.gerir'],
            ],

            'Operação e segurança' => [
                ['admin/notificacoes/fila',       'bi-send',         'Fila de notificações', 'E-mails e SMS por enviar, falhados e reenvios.', 'sistema.configuracoes.gerir'],
                ['admin/notificacoes/templates',  'bi-envelope',     'Modelos de mensagem',  'Ativar/desativar notificações por canal.',       'sistema.configuracoes.gerir'],
                ['admin/notificacoes/logs',       'bi-list-check',   'Registo de envios',    'O que foi enviado e quanto custam os SMS.',      'sistema.configuracoes.gerir'],
                ['admin/progressoes',             'bi-arrow-up-right','Progressões',         'Quem passou de fase e como se qualificou.',      'concurso.resultados.homologar'],
                ['admin/sistema/auditoria',       'bi-shield-check', 'Auditoria',            'Registo imutável de todas as ações.',            'sistema.auditoria.ver'],
                ['admin/sistema/configuracoes',   'bi-sliders',      'Parâmetros',           'Chaves de configuração do sistema.',             'sistema.configuracoes.gerir'],
            ],
        ];

        // Filtrar por permissão
        foreach ($secoes as $grupo => $cartoes) {
            $secoes[$grupo] = array_values(array_filter(
                $cartoes,
                static fn (array $c): bool => $u->can($c[4])
            ));

            if ($secoes[$grupo] === []) {
                unset($secoes[$grupo]);
            }
        }

        return view('admin/sistema/index', ['secoes' => $secoes]);
    }
}
