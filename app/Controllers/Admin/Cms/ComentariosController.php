<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Cms;

use App\Controllers\Admin\AdminBaseController;
use RuntimeException;

/** Moderação de comentários (ComentarioService — Fase 5). */
class ComentariosController extends AdminBaseController
{
    public function index()
    {
        $estado = $this->request->getGet('estado') ?: 'pendente';

        $model = model('ComentarioModel')
            ->select('noticias_comentarios.*, n.titulo AS noticia_titulo')
            ->join('noticias n', 'n.id = noticias_comentarios.noticia_id')
            ->where('noticias_comentarios.status', $estado);

        return view('admin/cms/comentarios/index', [
            'comentarios' => $model->orderBy('noticias_comentarios.created_at', 'DESC')->paginate(25),
            'pager'       => $model->pager,
            'estadoAtual' => $estado,
        ]);
    }

    /** $acao: aprovado | spam | lixeira */
    public function moderar(int $id, string $acao)
    {
        try {
            service('comentarios')->moderar($id, $acao);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('erro', $e->getMessage());
        }

        return redirect()->back()->with('sucesso', 'Comentário moderado.');
    }
}
