<?php

declare(strict_types=1);

/** Rotas do portal público. */
/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->get('/', 'Publico\HomeController::index');

// Notícias (slug — nunca ID, por SEO/Fase 5)
$routes->group('noticias', ['namespace' => 'App\Controllers\Publico'], static function ($routes) {
    $routes->get('/',                     'NoticiasController::index');
    $routes->get('categoria/(:segment)',  'NoticiasController::categoria/$1');
    $routes->get('tag/(:segment)',        'NoticiasController::tag/$1');
    $routes->post('comentar',             'NoticiasController::comentar', ['filter' => 'throttle:5,5']);
    $routes->get('(:segment)',            'NoticiasController::ver/$1');   // deve ficar por último
});

// Páginas institucionais (slug)
$routes->get('pagina/(:segment)', 'Publico\PaginasController::ver/$1');

// Resultados
$routes->group('resultados', ['namespace' => 'App\Controllers\Publico'], static function ($routes) {
    $routes->get('/',               'ResultadosController::index');
    $routes->get('evento/(:num)',   'ResultadosController::evento/$1');
    $routes->get('edicao/(:num)',   'ResultadosController::edicao/$1');
});

// Inscrição pública
$routes->group('inscricao', ['namespace' => 'App\Controllers\Publico'], static function ($routes) {
    $routes->get('/',                   'InscricaoController::formulario');
    $routes->post('/',                  'InscricaoController::submeter', ['filter' => 'throttle:3,10']);
    $routes->get('sucesso/(:segment)',  'InscricaoController::sucesso/$1');
    $routes->get('estado/(:segment)',   'InscricaoController::estado/$1');
    // Endpoints AJAX dos dropdowns dependentes
    $routes->get('municipios/(:num)',   'InscricaoController::municipiosPorProvincia/$1');
    $routes->get('escolas/(:num)',      'InscricaoController::escolasPorMunicipio/$1');
});

// Newsletter
$routes->post('newsletter/subscrever', 'Publico\NewsletterController::subscrever', ['filter' => 'throttle:3,10']);
$routes->get('newsletter/confirmar/(:segment)', 'Publico\NewsletterController::confirmar/$1');

// SEO
$routes->get('sitemap.xml', 'Publico\SeoController::sitemap');
$routes->get('feed',        'Publico\SeoController::feed');
