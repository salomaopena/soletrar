<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Notificacoes;

use App\Controllers\Admin\AdminBaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Modelos de mensagem (notificacoes_templates).
 *
 * Desativar um template DESLIGA esse canal para o respetivo evento —
 * sem deploy. Os textos suportam {{placeholders}}.
 */
class TemplatesController extends AdminBaseController
{
    public function index()
    {
        return view('admin/notificacoes/templates', [
            'templates' => model('NotificacaoTemplateModel')
                ->orderBy('codigo')->findAll(),
        ]);
    }

    public function editar(int $id)
    {
        return view('admin/notificacoes/template_form', [
            'template' => model('NotificacaoTemplateModel')->find($id)
                ?? throw PageNotFoundException::forPageNotFound(),
        ]);
    }

    public function atualizar(int $id)
    {
        if (! $this->validate([
            'nome'  => 'required|min_length[3]',
            'corpo' => 'required|min_length[5]',
        ])) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        model('NotificacaoTemplateModel')->update($id, [
            'nome'    => $this->request->getPost('nome'),
            'assunto' => $this->request->getPost('assunto') ?: null,
            'corpo'   => $this->request->getPost('corpo'),
            'ativo'   => $this->request->getPost('ativo') ? 1 : 0,
        ]);

        return redirect()->to('admin/notificacoes/templates')
            ->with('sucesso', 'Modelo atualizado.');
    }

    /** Liga/desliga rapidamente um canal. */
    public function alternar(int $id)
    {
        $t = model('NotificacaoTemplateModel')->find($id)
            ?? throw PageNotFoundException::forPageNotFound();

        model('NotificacaoTemplateModel')->update($id, ['ativo' => $t->ativo ? 0 : 1]);

        return redirect()->back()->with('sucesso',
            $t->ativo ? 'Modelo desativado (deixa de enviar).' : 'Modelo ativado.');
    }
}
