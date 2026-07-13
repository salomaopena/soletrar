<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Noticia;
use CodeIgniter\Model;

/**
 * Model de notícias: persistência, escopos de leitura e taxonomias N:N.
 * Regras de negócio ficam no NoticiaService.
 */
class NoticiaModel extends Model
{
    use \App\Traits\Auditavel;

    protected $table         = 'noticias';
    protected $primaryKey    = 'id';
    protected $returnType    = Noticia::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $protectFields = false;

    // Callbacks do trait Auditavel (Fase 4)
    protected $beforeUpdate = ['auditavelCapturarAntes'];
    protected $afterInsert  = ['auditavelAposInserir'];
    protected $afterUpdate  = ['auditavelAposAtualizar'];
    protected $afterDelete  = ['auditavelAposEliminar'];

    // ------------------------ Escopos de leitura ------------------------

    /** Apenas conteúdo visível no portal público, já com imagem destacada. */
    public function publicas(): static
    {
        return $this
            ->select('noticias.*, media_biblioteca.url AS imagem_destacada_url')
            ->join('media_biblioteca', 'media_biblioteca.id = noticias.imagem_destacada_id', 'left')
            ->where('noticias.status', 'publicada')
            ->where('noticias.visibilidade', 'publica')
            ->where('noticias.data_publicacao <=', utc_agora());
    }

    /** Ordenação do portal: fixadas primeiro, depois mais recentes. */
    public function ordemPortal(): static
    {
        return $this->orderBy('noticias.fixada', 'DESC')
                    ->orderBy('noticias.data_publicacao', 'DESC');
    }

    public function daCategoria(string $slugCategoria): static
    {
        return $this
            ->join('noticias_categorias_rel ncr', 'ncr.noticia_id = noticias.id')
            ->join('noticias_categorias nc', 'nc.id = ncr.categoria_id')
            ->where('nc.slug', $slugCategoria);
    }

    public function comTag(string $slugTag): static
    {
        return $this
            ->join('noticias_tags_rel ntr', 'ntr.noticia_id = noticias.id')
            ->join('noticias_tags nt', 'nt.id = ntr.tag_id')
            ->where('nt.slug', $slugTag);
    }

    /** Pesquisa FULLTEXT (índice ft_noticia da Fase 2). */
    public function pesquisar(string $termo): static
    {
        return $this->where(
            'MATCH(noticias.titulo, noticias.subtitulo, noticias.resumo, noticias.conteudo) '
            . 'AGAINST(' . $this->db->escape($termo) . ' IN NATURAL LANGUAGE MODE)',
            null,
            false
        );
    }

    public function slugExiste(string $slug, ?int $ignorarId = null): bool
    {
        $builder = $this->builder()->where('slug', $slug);
        if ($ignorarId !== null) {
            $builder->where('id !=', $ignorarId);
        }

        return $builder->countAllResults() > 0;
    }

    /** Contadores por estado para a barra de filtros do backoffice. */
    public function contadoresPorEstado(): array
    {
        $linhas = $this->builder()
            ->select('status, COUNT(*) AS total')
            ->where('deleted_at', null)
            ->groupBy('status')
            ->get()->getResultArray();

        return array_column($linhas, 'total', 'status');
    }

    // -------------------------- Taxonomias N:N --------------------------

    public function sincronizarCategorias(int $noticiaId, array $categoriaIds): void
    {
        $this->db->table('noticias_categorias_rel')->where('noticia_id', $noticiaId)->delete();

        $linhas = array_map(
            static fn ($id) => ['noticia_id' => $noticiaId, 'categoria_id' => (int) $id],
            array_filter($categoriaIds)
        );

        if ($linhas !== []) {
            $this->db->table('noticias_categorias_rel')->insertBatch($linhas);
        }
    }

    /** Recebe nomes livres; cria as tags inexistentes (comportamento WordPress). */
    public function sincronizarTags(int $noticiaId, array $nomes): void
    {
        helper('texto');
        $this->db->table('noticias_tags_rel')->where('noticia_id', $noticiaId)->delete();

        $linhas = [];
        foreach (array_unique(array_filter(array_map('trim', $nomes))) as $nome) {
            $slug = slug_pt($nome);
            $tag  = $this->db->table('noticias_tags')->where('slug', $slug)->get()->getRow();

            $tagId = $tag->id ?? null;
            if ($tagId === null) {
                $this->db->table('noticias_tags')->insert([
                    'nome' => $nome, 'slug' => $slug, 'created_at' => utc_agora(),
                ]);
                $tagId = $this->db->insertID();
            }

            $linhas[] = ['noticia_id' => $noticiaId, 'tag_id' => $tagId];
        }

        if ($linhas !== []) {
            $this->db->table('noticias_tags_rel')->insertBatch($linhas);
        }
    }

    /** Incremento de visualizações sem tocar em updated_at. */
    public function registarVisualizacao(int $id): void
    {
        $this->db->table($this->table)
            ->where('id', $id)
            ->set('visualizacoes', 'visualizacoes + 1', false)
            ->update();
    }
}
