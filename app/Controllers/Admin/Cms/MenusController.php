<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Cms;

use App\Controllers\Admin\AdminBaseController;

/**
 * Gestor de menus (tabelas `menus` e `menus_itens`).
 *
 * Permite preencher os menus do portal pela interface — sem SQL manual.
 * Qualquer escrita invalida a cache do MenuService.
 */
class MenusController extends AdminBaseController
{
    public function index()
    {
        $menus = model('MenuModel')->orderBy('localizacao')->findAll();

        // Garante que existem os dois menus base (header e footer).
        if ($menus === []) {
            model('MenuModel')->insertBatch([
                ['nome' => 'Menu principal', 'localizacao' => 'header', 'ativo' => 1],
                ['nome' => 'Menu do rodapé', 'localizacao' => 'footer', 'ativo' => 1],
            ]);
            $menus = model('MenuModel')->orderBy('localizacao')->findAll();
        }

        $itens = [];
        foreach ($menus as $menu) {
            $itens[$menu->id] = model('MenuItemModel')
                ->where('menu_id', $menu->id)
                ->orderBy('ordem')
                ->findAll();
        }

        return view('admin/cms/menus/index', [
            'menus'      => $menus,
            'itens'      => $itens,
            'paginas'    => model('PaginaModel')->where('status', 'publicada')->orderBy('titulo')->findAll(),
            'categorias' => model('NoticiaCategoriaModel')->orderBy('nome')->findAll(),
        ]);
    }

    public function guardarItem()
    {
        if (! $this->validate([
            'menu_id' => 'required|is_natural_no_zero',
            'label'   => 'required|max_length[100]',
            'tipo'    => 'required|in_list[pagina,noticia,categoria,url_externa,custom]',
        ])) {
            return redirect()->back()->with('erros', $this->validator->getErrors());
        }

        $tipo = $this->request->getPost('tipo');

        $dados = [
            'menu_id'   => (int) $this->request->getPost('menu_id'),
            'label'     => $this->request->getPost('label'),   // coluna real: `label`
            'tipo'      => $tipo,
            'target'    => $this->request->getPost('target') ?: '_self',
            'ordem'     => (int) ($this->request->getPost('ordem') ?: 0),
            'parent_id' => null,
            // Só um destes é preenchido, conforme o tipo:
            'pagina_id'    => $tipo === 'pagina'    ? (int) $this->request->getPost('pagina_id') : null,
            'categoria_id' => $tipo === 'categoria' ? (int) $this->request->getPost('categoria_id') : null,
            'url'          => in_array($tipo, ['url_externa', 'custom'], true)
                ? $this->request->getPost('url') : null,
        ];

        model('MenuItemModel')->insert($dados);
        $this->invalidarCaches();

        return redirect()->back()->with('sucesso', 'Item de menu adicionado.');
    }

    public function eliminarItem(int $id)
    {
        model('MenuItemModel')->delete($id);
        $this->invalidarCaches();

        return redirect()->back()->with('sucesso', 'Item removido.');
    }

    private function invalidarCaches(): void
    {
        foreach (['header', 'footer'] as $localizacao) {
            service('menus')->invalidarCache($localizacao);
        }
    }
}
