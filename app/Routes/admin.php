<?php

declare(strict_types=1);

/** Rotas da área administrativa. */
/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('admin', [
    'namespace' => 'App\Controllers\Admin',
    'filter'    => ['session', 'escopo', 'auditoria'],   // autentica → escopo → audita
], static function ($routes) {

    $routes->get('/',              'DashboardController::index');
    $routes->get('sem-atribuicao', 'DashboardController::semAtribuicao');

    // Perfil do próprio utilizador (perfis_utilizador)
    $routes->get('perfil',  'Sistema\PerfilController::ver');
    $routes->post('perfil', 'Sistema\PerfilController::guardar');

    // ================= INSCRIÇÕES =================
    $routes->group('inscricoes', static function ($routes) {
        $routes->get('/',    'Inscricoes\InscricoesController::index',   ['filter' => 'permission:inscricoes.validar']);
        $routes->get('nova', 'Inscricoes\InscricoesController::nova',    ['filter' => 'permission:inscricoes.criar']);
        $routes->post('/',   'Inscricoes\InscricoesController::guardar', ['filter' => 'permission:inscricoes.criar']);
        $routes->get('ver/(:segment)',       'Inscricoes\InscricoesController::ver/$1',      ['filter' => 'permission:inscricoes.validar']);
        $routes->post('validar/(:segment)',  'Inscricoes\InscricoesController::validar/$1',  ['filter' => 'permission:inscricoes.validar']);
        $routes->post('rejeitar/(:segment)', 'Inscricoes\InscricoesController::rejeitar/$1', ['filter' => 'permission:inscricoes.validar']);
    });

    // ============ PESQUISA DE CANDIDATOS (+ impressão e CSV) ============
    $routes->group('candidatos', ['filter' => 'permission:inscricoes.validar'], static function ($routes) {
        $routes->get('/',                'Inscricoes\CandidatosController::index');
        $routes->get('imprimir',         'Inscricoes\CandidatosController::imprimir');
        $routes->get('exportar',         'Inscricoes\CandidatosController::exportar');
        $routes->get('ficha/(:segment)', 'Inscricoes\CandidatosController::ficha/$1');
    });

    // ================= RELATÓRIOS =================
    $routes->group('relatorios', ['filter' => 'permission:inscricoes.validar'], static function ($routes) {
        $routes->get('/',                    'Relatorios\RelatoriosController::index');
        $routes->get('palavras',             'Relatorios\RelatoriosController::palavras');
        $routes->get('provincias/exportar',  'Relatorios\RelatoriosController::exportarProvincias');
    });

    // ================= CONCURSO =================
    $routes->group('edicoes', ['filter' => 'permission:concurso.edicoes.gerir'], static function ($routes) {
        $routes->get('/',             'Concurso\EdicoesController::index');
        $routes->get('nova',          'Concurso\EdicoesController::nova');
        $routes->post('/',            'Concurso\EdicoesController::guardar');
        $routes->get('editar/(:num)', 'Concurso\EdicoesController::editar/$1');
        $routes->post('(:num)',       'Concurso\EdicoesController::atualizar/$1');
    });

    $routes->group('categorias', ['filter' => 'permission:concurso.edicoes.gerir'], static function ($routes) {
        $routes->get('/',             'Concurso\CategoriasController::index');
        $routes->get('nova',          'Concurso\CategoriasController::nova');
        $routes->post('/',            'Concurso\CategoriasController::guardar');
        $routes->get('editar/(:num)', 'Concurso\CategoriasController::editar/$1');
        $routes->post('(:num)',       'Concurso\CategoriasController::atualizar/$1');
    });

    $routes->group('fases', ['filter' => 'permission:concurso.edicoes.gerir'], static function ($routes) {
        $routes->get('/',             'Concurso\FasesController::index');
        $routes->get('nova',          'Concurso\FasesController::nova');
        $routes->post('/',            'Concurso\FasesController::guardar');
        $routes->get('editar/(:num)', 'Concurso\FasesController::editar/$1');
        $routes->post('(:num)',       'Concurso\FasesController::atualizar/$1');
    });

    $routes->group('locais', ['filter' => 'permission:concurso.eventos.gerir'], static function ($routes) {
        $routes->get('/',             'Concurso\LocaisController::index');
        $routes->get('nova',          'Concurso\LocaisController::nova');
        $routes->post('/',            'Concurso\LocaisController::guardar');
        $routes->get('editar/(:num)', 'Concurso\LocaisController::editar/$1');
        $routes->post('(:num)',       'Concurso\LocaisController::atualizar/$1');
    });

    // ---------- Eventos: CRUD + júri + participantes + pool ----------
    $routes->group('eventos', ['filter' => 'permission:concurso.eventos.gerir'], static function ($routes) {
        $routes->get('/',             'Concurso\EventosController::index');
        $routes->get('nova',          'Concurso\EventosController::nova');
        $routes->post('/',            'Concurso\EventosController::guardar');
        $routes->get('editar/(:num)', 'Concurso\EventosController::editar/$1');
        $routes->post('(:num)',       'Concurso\EventosController::atualizar/$1');

        $routes->get('(:num)',        'Concurso\EventosController::ver/$1');        // sala de controlo
        $routes->get('(:num)/lista',  'Concurso\EventosController::lista/$1');      // pauta imprimível
        $routes->get('(:num)/recalcular',  'Concurso\EventosController::confirmarRecalculo/$1');
        $routes->post('(:num)/recalcular', 'Concurso\EventosController::recalcular/$1');

        $routes->post('(:num)/juri',                    'Concurso\EventosController::atribuirJuri/$1');
        $routes->post('(:num)/juri/(:num)/remover',     'Concurso\EventosController::removerJuri/$1/$2');
        $routes->post('(:num)/participantes',           'Concurso\EventosController::confirmarParticipantes/$1');
        $routes->post('(:num)/presenca/(:num)',         'Concurso\EventosController::presenca/$1/$2');
        $routes->post('(:num)/pool',                'Concurso\EventosController::montarPool/$1');
        $routes->get('(:num)/pool',                 'Concurso\EventosController::pool/$1');
        $routes->post('(:num)/pool/(:num)/remover',  'Concurso\EventosController::removerDoPool/$1/$2');
        $routes->post('(:num)/pool/(:num)/devolver', 'Concurso\EventosController::devolverAoPool/$1/$2');

        // Prémios (só faz sentido depois de o evento estar concluído)
        $routes->get('(:num)/premios',           'Concurso\PremiacaoController::ver/$1');
        $routes->post('(:num)/premios/atribuir', 'Concurso\PremiacaoController::atribuir/$1');
        $routes->get('(:num)/premios/imprimir',  'Concurso\PremiacaoController::imprimir/$1');
        $routes->get('(:num)/tentativas',           'Concurso\EventosController::tentativas/$1');
        $routes->get('(:num)/rounds',               'Concurso\EventosController::rounds/$1');
        $routes->post('(:num)/pool/adicionar',      'Concurso\EventosController::adicionarAoPool/$1');
        $routes->post('(:num)/pool/limpar',         'Concurso\EventosController::limparPool/$1');
        $routes->post('(:num)/iniciar',                 'Concurso\EventosController::iniciar/$1');
        $routes->post('(:num)/homologar', 'Concurso\ResultadosController::homologar/$1',
            ['filter' => 'permission:concurso.resultados.homologar']);
    });

    // ---------- Progressões entre fases (RN-04) ----------
    $routes->group('progressoes', ['filter' => 'permission:concurso.resultados.homologar'],
        static function ($routes) {
            $routes->get('/',      'Concurso\ProgressoesController::index');
            $routes->post('manual','Concurso\ProgressoesController::manual');
            $routes->post('(:num)/remover', 'Concurso\ProgressoesController::remover/$1');
        });

    // ---------- Palco ao vivo ----------
    $routes->group('palco', ['filter' => 'permission:concurso.juri.avaliar'], static function ($routes) {
        $routes->get('(:num)',                     'Concurso\PalcoController::painel/$1');
        $routes->post('round/abrir/(:num)',        'Concurso\PalcoController::abrirRound/$1');
        $routes->post('vez/(:num)/(:num)',         'Concurso\PalcoController::iniciarVez/$1/$2');
        $routes->post('tentativa/(:num)/pedido',   'Concurso\PalcoController::registarPedido/$1');
        $routes->post('tentativa/(:num)/avaliar',  'Concurso\PalcoController::avaliar/$1');
        $routes->post('tentativa/(:num)/apelacao', 'Concurso\PalcoController::apelacao/$1');
        $routes->post('round/concluir/(:num)',     'Concurso\PalcoController::concluirRound/$1');
        $routes->post('evento/concluir/(:num)',    'Concurso\PalcoController::concluirEvento/$1');
    });

    // ================= BANCO DE PALAVRAS =================
    $routes->group('palavras', ['filter' => 'permission:palavras.gerir'], static function ($routes) {
        // Categorias ANTES das rotas com (:num), para não colidir
        $routes->get('categorias',             'Palavras\CategoriasController::index');
        $routes->get('categorias/nova',        'Palavras\CategoriasController::nova');
        $routes->post('categorias',            'Palavras\CategoriasController::guardar');
        $routes->get('categorias/editar/(:num)', 'Palavras\CategoriasController::editar/$1');
        $routes->post('categorias/(:num)',     'Palavras\CategoriasController::atualizar/$1');

        $routes->get('/',             'Palavras\PalavrasController::index');
        $routes->get('nova',          'Palavras\PalavrasController::nova');
        $routes->post('/',            'Palavras\PalavrasController::guardar');
        $routes->get('editar/(:num)', 'Palavras\PalavrasController::editar/$1');
        $routes->post('(:num)',       'Palavras\PalavrasController::atualizar/$1');
        $routes->post('(:num)/validar',   'Palavras\PalavrasController::validar/$1',
            ['filter' => 'permission:palavras.validar']);
        $routes->post('(:num)/invalidar', 'Palavras\PalavrasController::invalidar/$1',
            ['filter' => 'permission:palavras.validar']);
        $routes->post('validar-varias',   'Palavras\PalavrasController::validarVarias',
            ['filter' => 'permission:palavras.validar']);
    });

    // ================= CMS =================
    $routes->group('cms', static function ($routes) {
        $routes->group('noticias', ['filter' => 'permission:cms.conteudo.criar'], static function ($routes) {
            $routes->get('/',                          'Cms\NoticiasController::index');
            $routes->get('nova',                       'Cms\NoticiasController::nova');
            $routes->post('/',                         'Cms\NoticiasController::guardar');
            $routes->get('editar/(:num)',              'Cms\NoticiasController::editar/$1');
            $routes->post('(:num)',                    'Cms\NoticiasController::atualizar/$1');
            $routes->post('(:num)/transitar/(:alpha)', 'Cms\NoticiasController::transitar/$1/$2');
        });

        $routes->group('categorias', ['filter' => 'permission:cms.conteudo.criar'], static function ($routes) {
            $routes->get('/',             'Cms\CategoriasController::index');
            $routes->get('nova',          'Cms\CategoriasController::nova');
            $routes->post('/',            'Cms\CategoriasController::guardar');
            $routes->get('editar/(:num)', 'Cms\CategoriasController::editar/$1');
            $routes->post('(:num)',       'Cms\CategoriasController::atualizar/$1');
        });

        $routes->group('media', ['filter' => 'permission:cms.media.gerir'], static function ($routes) {
            $routes->get('/',                'Cms\MediaController::index');
            $routes->post('enviar',          'Cms\MediaController::enviar');
            $routes->post('(:num)/eliminar', 'Cms\MediaController::eliminar/$1');
        });

        $routes->group('paginas', ['filter' => 'permission:cms.conteudo.criar'], static function ($routes) {
            $routes->get('/',             'Cms\PaginasController::index');
            $routes->get('nova',          'Cms\PaginasController::nova');
            $routes->post('/',            'Cms\PaginasController::guardar');
            $routes->get('editar/(:num)', 'Cms\PaginasController::editar/$1');
            $routes->post('(:num)',       'Cms\PaginasController::atualizar/$1');
        });

        $routes->group('menus', ['filter' => 'permission:cms.conteudo.publicar'], static function ($routes) {
            $routes->get('/',                     'Cms\MenusController::index');
            $routes->post('item',                 'Cms\MenusController::guardarItem');
            $routes->post('item/(:num)/eliminar', 'Cms\MenusController::eliminarItem/$1');
        });

        $routes->group('comentarios', ['filter' => 'permission:cms.comentarios.moderar'], static function ($routes) {
            $routes->get('/',                'Cms\ComentariosController::index');
            $routes->post('(:num)/(:alpha)', 'Cms\ComentariosController::moderar/$1/$2');
        });
    });

    // ================= PARCERIAS =================
    $routes->group('parcerias', ['filter' => 'permission:sistema.configuracoes.gerir'], static function ($routes) {
        $routes->get('patrocinadores',             'Parcerias\PatrocinadoresController::index');
        $routes->get('patrocinadores/nova',        'Parcerias\PatrocinadoresController::nova');
        $routes->post('patrocinadores',            'Parcerias\PatrocinadoresController::guardar');
        $routes->get('patrocinadores/editar/(:num)', 'Parcerias\PatrocinadoresController::editar/$1');
        $routes->post('patrocinadores/(:num)',     'Parcerias\PatrocinadoresController::atualizar/$1');

        $routes->get('premios',             'Parcerias\PremiosController::index');
        $routes->get('premios/nova',        'Parcerias\PremiosController::nova');
        $routes->post('premios',            'Parcerias\PremiosController::guardar');
        $routes->get('premios/editar/(:num)', 'Parcerias\PremiosController::editar/$1');
        $routes->post('premios/(:num)',     'Parcerias\PremiosController::atualizar/$1');
    });

    // ================= CAPACITAÇÕES =================
    $routes->group('capacitacoes', ['filter' => 'permission:sistema.configuracoes.gerir'], static function ($routes) {
        $routes->get('/',             'Capacitacoes\CapacitacoesController::index');
        $routes->get('nova',          'Capacitacoes\CapacitacoesController::nova');
        $routes->post('/',            'Capacitacoes\CapacitacoesController::guardar');
        $routes->get('editar/(:num)', 'Capacitacoes\CapacitacoesController::editar/$1');
        $routes->post('(:num)',       'Capacitacoes\CapacitacoesController::atualizar/$1');
    });

    // ================= GEOGRAFIA =================
    $routes->group('geografia', ['filter' => 'permission:sistema.utilizadores.gerir'], static function ($routes) {
        $routes->get('escolas',              'Geografia\EscolasController::index');
        $routes->get('escolas/nova',         'Geografia\EscolasController::nova');
        $routes->post('escolas',             'Geografia\EscolasController::guardar');
        $routes->get('escolas/editar/(:num)','Geografia\EscolasController::editar/$1');
        $routes->post('escolas/(:num)',      'Geografia\EscolasController::atualizar/$1');

        $routes->get('municipios',              'Geografia\MunicipiosController::index');
        $routes->get('municipios/nova',         'Geografia\MunicipiosController::nova');
        $routes->post('municipios',             'Geografia\MunicipiosController::guardar');
        $routes->get('municipios/editar/(:num)','Geografia\MunicipiosController::editar/$1');
        $routes->post('municipios/(:num)',      'Geografia\MunicipiosController::atualizar/$1');
    });

    // ================= NOTIFICAÇÕES INTERNAS (sino) — todos =================
    $routes->get('notificacoes',              'NotificacoesController::index');
    $routes->post('notificacoes/(:num)/lida', 'NotificacoesController::marcarLida/$1');
    $routes->post('notificacoes/todas-lidas', 'NotificacoesController::marcarTodasLidas');

    // ================= NOTIFICAÇÕES (operação) =================
    $routes->group('notificacoes', ['filter' => 'permission:sistema.configuracoes.gerir'], static function ($routes) {
        $routes->get('logs', 'Notificacoes\LogsController::index');
        $routes->get('fila',                      'Notificacoes\FilaController::index');
        $routes->post('fila/(:num)/reenfileirar', 'Notificacoes\FilaController::reenfileirar/$1');
        $routes->get('templates',                    'Notificacoes\TemplatesController::index');
        $routes->get('templates/editar/(:num)',      'Notificacoes\TemplatesController::editar/$1');
        $routes->post('templates/(:num)',            'Notificacoes\TemplatesController::atualizar/$1');
        $routes->post('templates/(:num)/alternar',   'Notificacoes\TemplatesController::alternar/$1');
    });

    // ================= SISTEMA =================
    $routes->group('sistema', ['filter' => 'permission:sistema.utilizadores.gerir'], static function ($routes) {
        // Página-portal das configurações (cartões de acesso)
        $routes->get('/', 'Sistema\SistemaController::index');

        $routes->get('utilizadores',            'Sistema\UtilizadoresController::index');
        $routes->get('utilizadores/nova',       'Sistema\UtilizadoresController::nova');
        $routes->post('utilizadores',           'Sistema\UtilizadoresController::guardar');
        $routes->post('utilizadores/(:num)/estado', 'Sistema\UtilizadoresController::alternarEstado/$1');

        // Âmbito territorial (coordenadores_atribuicao)
        $routes->get('utilizadores/(:num)/atribuicoes',  'Sistema\AtribuicoesController::utilizador/$1');
        $routes->post('utilizadores/(:num)/atribuicoes', 'Sistema\AtribuicoesController::guardar/$1');
        $routes->post('utilizadores/(:num)/atribuicoes/(:num)/alternar',
            'Sistema\AtribuicoesController::alternar/$1/$2');
        $routes->get('auditoria',     'Sistema\AuditoriaController::index', ['filter' => 'permission:sistema.auditoria.ver']);
        $routes->get('configuracoes', 'Sistema\ConfiguracoesController::index');
    });
});
