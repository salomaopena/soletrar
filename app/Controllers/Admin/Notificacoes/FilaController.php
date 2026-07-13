<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Notificacoes;

use App\Controllers\Admin\AdminBaseController;

/** Monitorização da fila de notificações (Fase 7). */
class FilaController extends AdminBaseController
{
    public function index()
    {
        $estado = $this->request->getGet('status') ?: 'pendente';

        $model = model('NotificacaoFilaModel')->where('status', $estado);

        $db = db_connect();
        $contadores = [];
        foreach (['pendente', 'a_enviar', 'enviada', 'falhada'] as $s) {
            $contadores[$s] = $db->table('notificacoes_fila')->where('status', $s)->countAllResults();
        }

        return view('admin/notificacoes/fila', [
            'mensagens'   => $model->orderBy('id', 'DESC')->paginate(30),
            'pager'       => $model->pager,
            'contadores'  => $contadores,
            'estadoAtual' => $estado,
        ]);
    }

    /** Recoloca uma mensagem falhada na fila (zera tentativas). */
    public function reenfileirar(int $id)
    {
        model('NotificacaoFilaModel')->update($id, [
            'status'               => 'pendente',
            'tentativas'           => 0,
            'proxima_tentativa_em' => null,
            'erro_ultimo'          => null,
        ]);

        return redirect()->back()->with('sucesso', 'Mensagem recolocada na fila.');
    }
}
