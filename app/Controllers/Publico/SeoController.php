<?php

declare(strict_types=1);

namespace App\Controllers\Publico;

use App\Controllers\BaseController;

/**
 * sitemap.xml e feed RSS gerados a partir de conteúdo publicado.
 */
class SeoController extends BaseController
{
    public function sitemap()
    {
        $noticias = model('NoticiaModel')->publicas()->orderBy('data_publicacao', 'DESC')->findAll(500);

        return $this->response
            ->setContentType('application/xml')
            ->setBody(view('publico/seo/sitemap', ['noticias' => $noticias]));
    }

    public function feed()
    {
        $noticias = model('NoticiaModel')->publicas()->ordemPortal()->limit(20)->find();

        return $this->response
            ->setContentType('application/rss+xml')
            ->setBody(view('publico/seo/feed', ['noticias' => $noticias]));
    }
}
