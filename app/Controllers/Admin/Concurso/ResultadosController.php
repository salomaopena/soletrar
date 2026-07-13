<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Concurso;

use App\Controllers\Admin\AdminBaseController;
use RuntimeException;

/**
 * Homologação de resultados: sela a classificação, dispara a progressão
 * para a fase seguinte e notifica os encarregados (Fase 6).
 */
class ResultadosController extends AdminBaseController
{
    public function homologar(int $eventoId)
    {
        try {
            service('classificacao')->homologar($eventoId, auth()->id());
        } catch (RuntimeException $e) {
            return redirect()->back()->with('erro', $e->getMessage());
        }

        return redirect()->back()
            ->with('sucesso', 'Resultados homologados, progressão apurada e notificações enviadas.');
    }
}
