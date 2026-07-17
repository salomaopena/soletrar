<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Concurso;

use App\Controllers\Admin\AdminBaseController;
use RuntimeException;

/**
 * PROGRESSÕES ENTRE FASES (RN-04).
 *
 * Cada linha de `progressoes_fase` é a prova auditável de como um
 * candidato se qualificou: fase de origem, evento, posição obtida e
 * quem homologou. É a tabela que a fase seguinte lê para saber quem
 * pode participar.
 *
 * Preenchimento:
 *   - AUTOMÁTICO: ao homologar um evento (ClassificacaoService::homologar
 *     → ProgressaoService::apurarQualificados) os N primeiros passam.
 *   - MANUAL: repescagem, convite ou substituição (ex.: um qualificado
 *     desiste) — exige motivo escrito, e fica igualmente auditado.
 */
class ProgressoesController extends AdminBaseController
{
    public function index()
    {
        $faseId = $this->request->getGet('fase_id');

        $q = db_connect()->table('progressoes_fase pf')
            ->select('pf.id, pf.tipo, pf.posicao_qualificacao, pf.observacoes, pf.created_at,
                      c.nome_completo, c.numero_inscricao,
                      fo.nome AS fase_origem, fd.nome AS fase_destino,
                      ev.nome AS evento, u.username AS aprovada_por')
            ->join('inscricoes i', 'i.id = pf.inscricao_id')
            ->join('candidatos c', 'c.id = i.candidato_id')
            ->join('fases_concurso fo', 'fo.id = pf.fase_origem_id')
            ->join('fases_concurso fd', 'fd.id = pf.fase_destino_id')
            ->join('eventos_competicao ev', 'ev.id = pf.evento_origem_id', 'left')
            ->join('users u', 'u.id = pf.aprovada_por', 'left');

        if (! $this->escopo->eNacional() && $this->escopo->provincias !== []) {
            $q->whereIn('i.provincia_id', $this->escopo->provincias);
        }

        if ($faseId) {
            $q->where('pf.fase_destino_id', (int) $faseId);
        }

        return view('admin/concurso/progressoes', [
            'progressoes' => $q->orderBy('pf.id', 'DESC')->get(200)->getResult(),
            'fases'       => model('FaseModel')->orderBy('ordem')->findAll(),
            'faseAtual'   => $faseId,
        ]);
    }

    /**
     * Remove uma progressão registada por engano (ex.: candidato que
     * nunca competiu foi indevidamente apurado antes de existir a
     * salvaguarda em ClassificacaoService::homologar()). Fica auditado.
     *
     * NÃO retira automaticamente a inscrição de eventos onde já tenha
     * sido confirmada como participante — se já houver participação
     * criada na fase seguinte, reveja-a manualmente.
     */
    public function remover(int $id)
    {
        $progressao = db_connect()->table('progressoes_fase')->where('id', $id)->get()->getRow();

        if ($progressao === null) {
            return redirect()->back()->with('erro', 'Progressão não encontrada.');
        }

        db_connect()->table('progressoes_fase')->where('id', $id)->delete();

        service('auditoria')->registar(
            'remover_progressao', 'progressoes_fase', $id,
            dadosAntes: (array) $progressao,
            descricao: 'Progressão removida manualmente (correção de erro).'
        );

        return redirect()->back()->with('sucesso',
            'Progressão removida. Se o candidato já tinha sido confirmado num evento da fase '
            . 'seguinte, remova essa participação manualmente.');
    }

    /** Progressão manual (repescagem / convite / substituição). */
    public function manual()
    {
        if (! $this->validate([
            'inscricao_id'    => 'required|is_natural_no_zero',
            'fase_origem_id'  => 'required|is_natural_no_zero',
            'fase_destino_id' => 'required|is_natural_no_zero',
            'tipo'            => 'required|in_list[repescagem,convite,substituicao]',
            'observacoes'     => 'required|min_length[10]',
        ])) {
            return redirect()->back()->with('erros', $this->validator->getErrors());
        }

        try {
            service('progressao')->progredirManual(
                inscricaoId:   (int) $this->request->getPost('inscricao_id'),
                faseOrigemId:  (int) $this->request->getPost('fase_origem_id'),
                faseDestinoId: (int) $this->request->getPost('fase_destino_id'),
                tipo:          (string) $this->request->getPost('tipo'),
                observacao:    (string) $this->request->getPost('observacoes'),
                aprovadoPor:   auth()->id(),
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->with('erro', $e->getMessage());
        }

        return redirect()->back()->with('sucesso', 'Progressão manual registada e auditada.');
    }
}
