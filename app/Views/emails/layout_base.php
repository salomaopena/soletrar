<?php /* Layout HTML dos e-mails (CanalEmail — Fase 7).
   Regras de e-mail: tabelas + estilos inline; sem fontes externas
   (clientes de e-mail bloqueiam); largura 600px; cores do tema fixas. */ ?>
<!doctype html>
<html lang="pt-AO">
<body style="margin:0;padding:0;background:#F7F8FA;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F8FA;padding:24px 0;">
    <tr><td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0"
             style="background:#FFFFFF;border-radius:14px;overflow:hidden;border:1px solid #E6E9EF;">
        <tr>
          <td style="background:#232B66;padding:20px 32px;">
            <img src="<?= base_url('assets/img/logo-email.png') ?>" alt="Concurso Nacional de Soletração" height="40">
          </td>
        </tr>
        <tr>
          <td style="padding:32px;">
            <?php if (! empty($assunto)): ?>
              <h1 style="margin:0 0 16px;font-size:20px;color:#232B66;"><?= esc($assunto) ?></h1>
            <?php endif ?>
            <div style="font-size:15px;line-height:1.65;color:#1D2433;">
              <?= $conteudo /* já escapado + nl2br no CanalEmail */ ?>
            </div>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 32px;border-top:1px solid #E6E9EF;font-size:12px;color:#5A6478;">
            Mensagem automática do Concurso Nacional de Soletração — Angola. Por favor não responda a este e-mail.
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
