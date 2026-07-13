<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

/** Notificações internas do utilizador (o "sino"). */
class NotificacoesController extends AdminBaseController
{
    public function index()
    {
        $model = model('NotificacaoModel')->where('user_id', auth()->id());

        return view('admin/notificacoes/minhas', [
            'notificacoes' => $model->orderBy('id', 'DESC')->paginate(30),
            'pager'        => $model->pager,
        ]);
    }

    public function marcarLida(int $id)
    {
        model('NotificacaoModel')
            ->where('id', $id)->where('user_id', auth()->id())
            ->set(['lida' => 1, 'lida_em' => utc_agora()])
            ->update();

        return redirect()->back();
    }

    public function marcarTodasLidas()
    {
        model('NotificacaoModel')
            ->where('user_id', auth()->id())->where('lida', 0)
            ->set(['lida' => 1, 'lida_em' => utc_agora()])
            ->update();

        return redirect()->back()->with('sucesso', 'Notificações marcadas como lidas.');
    }
}
