<?php
/**
 * E-mail de recuperação de senha (Shield).
 *
 * Reaproveita o MESMO layout institucional dos outros e-mails do
 * sistema (emails/layout_base.php), que espera 'assunto' + 'conteudo'
 * diretamente (não usa extend/section) — por isso este ficheiro
 * chama-o explicitamente, em vez de o "estender".
 *
 * O link de recuperação vem tipicamente em $resetLink ou $link,
 * consoante a versão do Shield — o fallback abaixo cobre as duas.
 */
$link = $resetLink ?? $link ?? '#';

$conteudo = '<p>Recebemos um pedido para repor a senha da sua conta no '
    . 'Concurso Nacional de Soletração.</p>'
    . '<p style="text-align:center;margin:28px 0;">'
    . '<a href="' . esc($link, 'attr') . '" '
    . 'style="display:inline-block;padding:12px 28px;background:#2AA8A3;color:#fff;'
    . 'border-radius:999px;text-decoration:none;font-weight:700;">'
    . 'Definir nova senha</a></p>'
    . '<p style="color:#5A6478;font-size:13px;">Se não pediu esta recuperação, ignore esta '
    . 'mensagem — a sua senha atual continua válida. Este link expira por motivos de segurança.</p>';

echo view('emails/layout_base', [
    'assunto'  => 'Recuperação de senha',
    'conteudo' => $conteudo,
]);
