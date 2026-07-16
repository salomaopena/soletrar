<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;
use CodeIgniter\Shield\Filters\ChainAuth;
use CodeIgniter\Shield\Filters\GroupFilter;
use CodeIgniter\Shield\Filters\PermissionFilter;
use CodeIgniter\Shield\Filters\SessionAuth;

class Filters extends BaseConfig
{
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,

        // Shield
        'session'    => SessionAuth::class,
        'chain'      => ChainAuth::class,
        'group'      => GroupFilter::class,
        'permission' => PermissionFilter::class,

        // Projeto (Fase 4)
        'escopo'    => \App\Filters\EscopoProvincialFilter::class,
        'auditoria' => \App\Filters\AuditoriaFilter::class,
        'throttle'  => \App\Filters\ThrottleFilter::class,
    ];

    /**
     * IMPORTANTE: 'pagecache' foi retirado daqui.
     *
     * $required corre em TODA a rota, sem exceção possível — e o
     * PageCache grava o HTML final da resposta, servindo-o a qualquer
     * pedido seguinte ao mesmo URL sem voltar a correr o controller.
     * Isso é fatal em páginas com csrf_field(): o HTML em cache traz o
     * token de sessão de quem o gerou primeiro; todos os visitantes
     * seguintes recebem esse token, e o POST deles falha a verificação
     * CSRF ("The action you requested is not allowed"). Foi exactamente
     * o que aconteceu em GET/POST /inscricao.
     *
     * Regra: nunca aplicar 'pagecache' de forma global num site com
     * formulários/CSRF. Se algum dia se quiser cache de página, aplicar
     * só a rotas verdadeiramente estáticas, via $filters por rota — nunca
     * aqui.
     */
    public array $required = [
        'before' => ['forcehttps'],
        'after'  => ['performance', 'toolbar'],
    ];

    public array $globals = [
        'before' => [
            //'csrf' => ['except' => ['api/*']],   // CSRF em TODA a web, exceto API
        ],
        'after' => [
            'secureheaders',
        ],
    ];

    public array $methods = [];
    public array $filters = [];
}
