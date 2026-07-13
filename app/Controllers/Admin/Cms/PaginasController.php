<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Cms;

use App\Controllers\Admin\AdminBaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * CRUD de páginas institucionais (Sobre, Regulamento, Contactos...).
 * O conteúdo é sanitizado à entrada pelo SanitizadorHtml (Fase 5).
 */
class PaginasController extends AdminBaseController
{
    private const ROTA = 'admin/cms/paginas';

    public function index()
    {
        return view('admin/crud/index', [
            'titulo'   => 'Páginas',
            'rotaBase' => self::ROTA,
            'colunas'  => [
                'titulo'        => 'Título',
                'slug'          => 'Endereço (slug)',
                'status'        => ['rotulo' => 'Estado', 'tipo' => 'badge'],
                'mostra_no_menu'=> ['rotulo' => 'No menu', 'tipo' => 'bool'],
                'updated_at'    => ['rotulo' => 'Atualizada', 'tipo' => 'data'],
            ],
            'registos' => model('PaginaModel')->orderBy('ordem')->orderBy('titulo')->findAll(),
            'vazio'    => 'Crie páginas institucionais (Sobre, Regulamento, Contactos) para o portal.',
        ]);
    }

    public function nova()
    {
        return view('admin/crud/formulario', [
            'titulo'   => 'Nova página',
            'rotaBase' => self::ROTA,
            'registo'  => null,
            'campos'   => $this->campos(),
        ]);
    }

    public function editar(int $id)
    {
        return view('admin/crud/formulario', [
            'titulo'   => 'Editar página',
            'rotaBase' => self::ROTA,
            'registo'  => model('PaginaModel')->find($id) ?? throw PageNotFoundException::forPageNotFound(),
            'campos'   => $this->campos(),
        ]);
    }

    public function guardar()
    {
        if (! $this->validate(['titulo' => 'required|min_length[3]'])) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        $dados = $this->dados();
        $dados['autor_id'] = auth()->id();

        model('PaginaModel')->insert($dados);

        return redirect()->to(self::ROTA)->with('sucesso', 'Página criada.');
    }

    public function atualizar(int $id)
    {
        if (! $this->validate(['titulo' => 'required|min_length[3]'])) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        model('PaginaModel')->update($id, $this->dados());

        return redirect()->to(self::ROTA)->with('sucesso', 'Página atualizada.');
    }

    private function campos(): array
    {
        return [
            ['nome' => 'titulo', 'rotulo' => 'Título', 'obrigatorio' => true, 'largura' => 8],
            ['nome' => 'status', 'rotulo' => 'Estado', 'tipo' => 'select', 'obrigatorio' => true, 'largura' => 4,
             'opcoes' => ['rascunho' => 'Rascunho', 'publicada' => 'Publicada', 'arquivada' => 'Arquivada']],
            ['nome' => 'conteudo', 'rotulo' => 'Conteúdo', 'tipo' => 'textarea', 'largura' => 12, 'linhas' => 14,
             'ajuda' => 'HTML permitido (sanitizado ao guardar).'],
            ['nome' => 'meta_titulo', 'rotulo' => 'Meta-título (SEO)', 'largura' => 6],
            ['nome' => 'ordem', 'rotulo' => 'Ordem', 'tipo' => 'number', 'largura' => 3],
            ['nome' => 'mostra_no_menu', 'rotulo' => 'Mostrar no menu', 'tipo' => 'checkbox'],
            ['nome' => 'meta_descricao', 'rotulo' => 'Meta-descrição (SEO)', 'tipo' => 'textarea', 'largura' => 12, 'linhas' => 2],
        ];
    }

    private function dados(): array
    {
        helper('texto');

        $d = $this->request->getPost([
            'titulo', 'conteudo', 'status', 'ordem', 'meta_titulo', 'meta_descricao',
        ]);

        $d['slug']           = slug_pt($d['titulo']);
        $d['ordem']          = (int) ($d['ordem'] ?: 0);
        $d['mostra_no_menu'] = $this->request->getPost('mostra_no_menu') ? 1 : 0;
        // Sanitização do HTML rico (mesma política das notícias).
        $d['conteudo']       = service('sanitizadorHtml')->limpar((string) $d['conteudo']);

        return $d;
    }
}
