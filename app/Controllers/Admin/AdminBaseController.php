<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Comum\Escopo;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller base da área administrativa.
 *
 * Disponibiliza $this->escopo (território do utilizador), resolvido pelo
 * EscopoProvincialFilter e partilhado por todos os controllers admin —
 * é a peça que os CRUDs das Fases 4 e 6 assumem existir.
 */
abstract class AdminBaseController extends BaseController
{
    protected Escopo $escopo;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // O filtro já resolveu e partilhou o escopo; aqui apenas o expomos.
        $this->escopo = service('escopo')->doUtilizador(auth()->id());
    }
}
