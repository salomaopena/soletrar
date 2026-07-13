<?php

declare(strict_types=1);

namespace App\Services\Cms;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Menus dinâmicos por localização (header, footer, ...).
 *
 * A árvore é montada uma vez e guardada em cache; qualquer alteração
 * administrativa invalida a cache. As views só consomem arvore().
 */
final class MenuService
{
    private const TTL_CACHE = 3600; // 1 hora (invalidada em cada escrita)

    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /**
     * Devolve a árvore de itens de uma localização, pronta para render:
     * cada item tem label, url, target, icone e filhos[].
     */
    public function arvore(string $localizacao): array
    {
        return cache()->remember(
            'menu_' . $localizacao,
            self::TTL_CACHE,
            fn () => $this->montarArvore($localizacao)
        );
    }

    /** Invalidar após qualquer escrita em menus/menus_itens. */
    public function invalidarCache(string $localizacao): void
    {
        cache()->delete('menu_' . $localizacao);
    }

    private function montarArvore(string $localizacao): array
    {
        $itens = $this->db->table('menus_itens mi')
            ->select('mi.*, p.slug AS pagina_slug, n.slug AS noticia_slug, nc.slug AS categoria_slug')
            ->join('menus m', 'm.id = mi.menu_id')
            ->join('paginas p', 'p.id = mi.pagina_id', 'left')
            ->join('noticias n', 'n.id = mi.noticia_id', 'left')
            ->join('noticias_categorias nc', 'nc.id = mi.categoria_id', 'left')
            ->where('m.localizacao', $localizacao)
            ->where('m.ativo', 1)
            ->orderBy('mi.ordem')
            ->get()->getResultArray();

        // Resolver a URL final de cada item conforme o tipo.
        foreach ($itens as &$item) {
            $item['url_final'] = match ($item['tipo']) {
                'pagina'      => site_url($item['pagina_slug'] ?? ''),
                'noticia'     => site_url('noticias/' . ($item['noticia_slug'] ?? '')),
                'categoria'   => site_url('noticias/categoria/' . ($item['categoria_slug'] ?? '')),
                'url_externa',
                'custom'      => $item['url'] ?? '#',
            };
            // A coluna na BD chama-se `label`; expomos também como `titulo`
            // para as views (compatibilidade e legibilidade).
            $item['titulo'] = $item['label'];
            $item['filhos'] = [];
        }
        unset($item);

        // Montagem da árvore em memória (2 passagens, O(n)).
        $porId = array_column($itens, null, 'id');
        $raiz  = [];

        foreach ($porId as $id => &$item) {
            if ($item['parent_id'] !== null && isset($porId[$item['parent_id']])) {
                $porId[$item['parent_id']]['filhos'][] = &$item;
            } else {
                $raiz[] = &$item;
            }
        }

        return $raiz;
    }
}
