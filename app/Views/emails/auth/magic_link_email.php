<?php
/**
 * E-mail com o link de acesso (magic link). Chave 'magic-link-email'.
 * Reaproveita o layout institucional (emails/layout_base.php), que
 * espera 'assunto' + 'conteudo' diretamente (sem extend/section).
 */
$link = $magicLink ?? $link ?? '#';

$conteudo = '<p>Recebemos um pedido de acesso à sua conta no '
    . 'Concurso Nacional de Soletração, sem senha.</p>'
    . '<p style="text-align:center;margin:28px 0;">'
    . '<a href="' . esc($link, 'attr') . '" '
    . 'style="display:inline-block;padding:12px 28px;background:#2AA8A3;color:#fff;'
    . 'border-radius:999px;text-decoration:none;font-weight:700;">'
    . 'Entrar agora</a></p>'
    . '<p style="color:#5A6478;font-size:13px;">Se não pediu este acesso, ignore esta '
    . 'mensagem. O link expira por motivos de segurança e só pode ser usado uma vez.</p>';

echo view('emails/layout_base', [
    'assunto'  => 'O seu link de acesso',
    'conteudo' => $conteudo,
]);
