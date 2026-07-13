<?php

declare(strict_types=1);

/** Rotas de autenticação (Shield) + webhooks fora do CSRF. */
/** @var \CodeIgniter\Router\RouteCollection $routes */

// Rotas base do Shield (login, logout, registo, recuperação...)
service('auth')->routes($routes);

// Webhook de entrega de SMS (pro2sms) — grupo api/* isento de CSRF (Fase 4).
$routes->post('api/sms/callback', 'Api\SmsCallbackController::receber');
