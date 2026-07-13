<?php

declare(strict_types=1);

namespace App\Services\Cms;

/**
 * Sanitização do HTML rico do editor (política: limpar na ENTRADA).
 *
 * NOTA IMPORTANTE (causa de um erro real):
 * o HTMLPurifier valida contra um doctype (XHTML 1.0 Transitional por
 * omissão) que NÃO conhece elementos HTML5 como <figure> e <figcaption>.
 * Pô-los em HTML.Allowed sem os registar provoca:
 *   "Element 'figure' is not supported".
 * Por isso são declarados explicitamente via addElement().
 */
final class SanitizadorHtml
{
    private \HTMLPurifier $purifier;

    /** Whitelist editorial. */
    private const HTML_PERMITIDO =
        'p,br,h2,h3,h4,ul,ol,li,a[href|title|rel|target],img[src|alt|width|height],'
        . 'blockquote,strong,em,b,i,u,s,figure,figcaption,table,thead,tbody,tr,th,td,'
        . 'caption,hr,code,pre,span';

    public function __construct()
    {
        $config = \HTMLPurifier_Config::createDefault();

        $config->set('HTML.Allowed', self::HTML_PERMITIDO);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.TargetBlank', true);   // links externos em nova aba
        $config->set('HTML.Nofollow', true);      // rel=nofollow em links externos
        $config->set('AutoFormat.AutoParagraph', true);   // texto simples → <p>
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('Cache.SerializerPath', WRITEPATH . 'cache');

        // Necessário para poder ESTENDER a definição de HTML:
        $config->set('HTML.DefinitionID', 'cns-soletracao-editorial');
        $config->set('HTML.DefinitionRev', 2);

        // Em desenvolvimento a cache é desligada para as alterações
        // à definição terem efeito imediato.
        if (ENVIRONMENT === 'development') {
            $config->set('Cache.DefinitionImpl', null);
        }

        // Registar os elementos HTML5 que o doctype base desconhece.
        if ($def = $config->maybeGetRawHTMLDefinition()) {
            $def->addElement('figure',     'Block',  'Flow',   'Common');
            $def->addElement('figcaption', 'Block',  'Flow',   'Common');
            $def->addElement('mark',       'Inline', 'Inline', 'Common');
            $def->addElement('time',       'Inline', 'Inline', 'Common', [
                'datetime' => 'Text',
            ]);
        }

        $this->purifier = new \HTMLPurifier($config);
    }

    public function limpar(string $html): string
    {
        return $this->purifier->purify($html);
    }
}
