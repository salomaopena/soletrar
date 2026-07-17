<?php

declare(strict_types=1);

namespace App\Controllers\Publico;

use App\Controllers\BaseController;

/**
 * Newsletter: subscrição com duplo opt-in (token de confirmação).
 */
class NewsletterController extends BaseController
{
    public function subscrever()
    {
        $email = $this->request->getPost('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('erro', 'E-mail inválido.');
        }

        $token = service('urlCrypt')->gerarTokenOpaco();

        // A tabela real é `newsletter_subscritores` (ver schema v2.0).
        model('SubscritorModel')->insert([
            'email'             => $email,
            'token_confirmacao' => $token,
            'confirmado'        => 0,
            'ip_inscricao'      => $this->request->getIPAddress(),
        ]);

        // Envio direto (não via Notificador/templates, para não depender
        // de um template semeado na BD só para este e-mail simples).
        $this->enviarEmailConfirmacao($email, $token);

        return redirect()->back()->with('sucesso', 'Verifique o seu e-mail para confirmar a subscrição.');
    }

    private function enviarEmailConfirmacao(string $email, string $token): void
    {
        $link = site_url('newsletter/confirmar/' . $token);

        $mensagem = service('email');
        $mensagem->setTo($email);
        $mensagem->setSubject('Confirme a sua subscrição — Concurso Nacional de Soletração');
        $mensagem->setMailType('html');
        $mensagem->setMessage(view('emails/layout_base', [
            'assunto'  => 'Confirme a sua subscrição',
            'conteudo' => 'Obrigado por subscrever as novidades do Concurso Nacional de Soletração.<br><br>'
                . '<a href="' . esc($link, 'attr') . '" '
                . 'style="display:inline-block;padding:10px 20px;background:#2AA8A3;color:#fff;'
                . 'border-radius:999px;text-decoration:none;">Confirmar subscrição</a><br><br>'
                . 'Se não pediu esta subscrição, pode ignorar esta mensagem.',
        ]));

        // Falha de envio não deve impedir o registo da subscrição —
        // fica só por confirmar, e pode reenviar-se depois.
        try {
            $mensagem->send();
        } catch (\Throwable $e) {
            log_message('error', 'Newsletter: falha ao enviar e-mail de confirmação para {email}: {erro}', [
                'email' => $email, 'erro' => $e->getMessage(),
            ]);
        }
    }

    public function confirmar(string $token)
    {
        $sub = model('SubscritorModel')->where('token_confirmacao', $token)->first();

        if ($sub !== null) {
            model('SubscritorModel')->update($sub->id, [
                'confirmado'       => 1,
                'data_confirmacao' => utc_agora(),
            ]);
        }

        return view('publico/newsletter/confirmado');
    }
}
