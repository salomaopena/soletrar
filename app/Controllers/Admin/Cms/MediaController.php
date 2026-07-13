<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Cms;

use App\Controllers\Admin\AdminBaseController;
use RuntimeException;

/** Biblioteca de media (usa o MediaService — Fase 5). */
class MediaController extends AdminBaseController
{
    public function index()
    {
        $model = model('MediaModel');

        return view('admin/cms/media/index', [
            'media' => $model->orderBy('created_at', 'DESC')->paginate(24),
            'pager' => $model->pager,
        ]);
    }

    public function enviar()
    {
        $ficheiro = $this->request->getFile('ficheiro');

        if ($ficheiro === null || ! $ficheiro->isValid()) {
            return redirect()->back()->with('erro', 'Selecione um ficheiro válido.');
        }

        try {
            service('media')->enviar($ficheiro, [
                'titulo'    => $this->request->getPost('titulo'),
                'texto_alt' => $this->request->getPost('texto_alt'),
                'legenda'   => $this->request->getPost('legenda'),
            ], auth()->id());
        } catch (RuntimeException $e) {
            return redirect()->back()->with('erro', $e->getMessage());
        }

        return redirect()->back()->with('sucesso', 'Ficheiro enviado.');
    }

    public function eliminar(int $id)
    {
        try {
            service('media')->eliminar($id);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('erro', $e->getMessage());
        }

        return redirect()->back()->with('sucesso', 'Ficheiro eliminado.');
    }
}
