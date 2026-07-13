<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Sistema;

use App\Controllers\Admin\AdminBaseController;

/** Consulta dos registos de auditoria (Fase 4). */
class AuditoriaController extends AdminBaseController
{
    public function index()
    {
        $model = model('AuditoriaModel')
            ->select('auditoria_logs.*, u.username')
            ->join('users u', 'u.id = auditoria_logs.user_id', 'left');

        if ($acao = $this->request->getGet('acao')) {
            $model->where('auditoria_logs.acao', $acao);
        }

        return view('admin/sistema/auditoria', [
            'registos' => $model->orderBy('auditoria_logs.id', 'DESC')->paginate(50),
            'pager'    => $model->pager,
        ]);
    }
}
