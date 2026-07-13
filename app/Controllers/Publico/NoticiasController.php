<?php

declare(strict_types=1);

namespace App\Controllers\Publico;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Portal público de notícias.
 *
 * Rotas:
 *   GET noticias                       → index (paginação, ?q= pesquisa)
 *   GET noticias/categoria/(:segment)  → categoria/$1
 *   GET noticias/tag/(:segment)        → tag/$1
 *   GET noticias/(:segment)            → ver/$1 (slug)
 */
class NoticiasController extends BaseController
{
    public function index()
    {
        $model = model('NoticiaModel')->publicas()->ordemPortal();

        if ($termo = $this->request->getGet('q')) {
            $model->pesquisar($termo);
        }

        return view('publico/noticias/index', [
            'noticias' => $model->paginate(12),
            'pager'    => $model->pager,
            'termo'    => $termo ?? '',
        ]);
    }

    public function categoria(string $slug)
    {
        $model = model('NoticiaModel')->publicas()->daCategoria($slug)->ordemPortal();

        return view('publico/noticias/index', [
            'noticias' => $model->paginate(12),
            'pager'    => $model->pager,
        ]);
    }

    public function tag(string $slug)
    {
        $model = model('NoticiaModel')->publicas()->comTag($slug)->ordemPortal();

        return view('publico/noticias/index', [
            'noticias' => $model->paginate(12),
            'pager'    => $model->pager,
        ]);
    }

    /** Submissão de comentário público (moderação + honeypot no service). */
    public function comentar()
    {
        if (! $this->validate('comentarioPublico')) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        try {
            $estado = service('comentarios')->criar([
                'noticia_id'        => (int) $this->request->getPost('noticia_id'),
                'parent_id'         => $this->request->getPost('parent_id') ?: null,
                'nome_autor'        => $this->request->getPost('nome_autor'),
                'email_autor'       => $this->request->getPost('email_autor'),
                'conteudo'          => $this->request->getPost('conteudo'),
                'website_confirmar' => $this->request->getPost('website_confirmar'), // honeypot
            ]);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('erro', $e->getMessage());
        }

        return redirect()->back()->with('sucesso', $estado === 'pendente'
            ? 'Comentário submetido — aguarda moderação.'
            : 'Comentário publicado.');
    }

    public function ver(string $slug)
    {
        $noticia = model('NoticiaModel')->publicas()->where('noticias.slug', $slug)->first()
            ?? throw PageNotFoundException::forPageNotFound();

        model('NoticiaModel')->registarVisualizacao($noticia->id);

        return view('publico/noticias/ver', [
            'noticia'      => $noticia,
            'comentarios'  => service('comentarios')->aprovadosDe($noticia->id),
            'relacionadas' => model('NoticiaModel')->publicas()
                ->where('noticias.id !=', $noticia->id)
                ->ordemPortal()->limit(3)->find(),
            // A view usa a entity para todo o SEO (metaTitulo, metaDescricao, ogImagem).
        ]);
    }
}
