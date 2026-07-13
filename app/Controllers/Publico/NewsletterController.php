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

        // TODO: enviar e-mail de confirmação com o link
        //   site_url('newsletter/confirmar/' . $token) via service('notificador').

        return redirect()->back()->with('sucesso', 'Verifique o seu e-mail para confirmar a subscrição.');
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
