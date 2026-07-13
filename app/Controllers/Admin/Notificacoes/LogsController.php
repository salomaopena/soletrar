<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Notificacoes;

use App\Controllers\Admin\AdminBaseController;

/**
 * Registo de envios: `logs_email` e `logs_sms`.
 *
 * Serve para duas perguntas operacionais:
 *   "a mensagem chegou?"  e  "quanto estamos a gastar em SMS?"
 */
class LogsController extends AdminBaseController
{
    public function index()
    {
        $canal = $this->request->getGet('canal') === 'email' ? 'email' : 'sms';
        $db    = db_connect();

        if ($canal === 'email') {
            $model = model('LogEmailModel');
            $logs  = $model->orderBy('id', 'DESC')->paginate(40);
        } else {
            $model = model('LogSmsModel');
            $logs  = $model->orderBy('id', 'DESC')->paginate(40);
        }

        // Custo de SMS nos últimos 30 dias
        $custo = $db->table('logs_sms')
            ->selectSum('custo', 'total')
            ->selectCount('id', 'mensagens')
            ->selectSum('partes', 'partes')
            ->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
            ->get()->getRow();

        return view('admin/notificacoes/logs', [
            'canal' => $canal,
            'logs'  => $logs,
            'pager' => $model->pager,
            'custo' => $custo,
        ]);
    }
}
