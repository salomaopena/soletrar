<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Cms;

use App\Controllers\Admin\AdminBaseController;
use App\Exceptions\AutorizacaoException;
use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;

/**
 * Backoffice editorial de notícias.
 *
 * As notícias são conteúdo público por natureza: usam ID direto na URL
 * (a cifra de parâmetros está reservada a dados pessoais — Fase 4).
 */
class NoticiasController extends AdminBaseController
{
    /** Listagem com barra de estados e contadores. */
    public function index()
    {
        $estadoAtual = $this->request->getGet('estado') ?: 'todas';

        $model = model('NoticiaModel')
            ->select('noticias.*, u.username AS autor_nome')
            ->join('users u', 'u.id = noticias.autor_id', 'left');

        if ($estadoAtual !== 'todas') {
            $model->where('noticias.status', $estadoAtual);
        }

        // Jornalistas só veem os próprios conteúdos.
        if (! auth()->user()->can('cms.conteudo.publicar')) {
            $model->where('noticias.autor_id', auth()->id());
        }

        return view('admin/cms/noticias/index', [
            'noticias'    => $model->orderBy('noticias.updated_at', 'DESC')->paginate(20),
            'pager'       => $model->pager,
            'contadores'  => model('NoticiaModel')->contadoresPorEstado(),
            'estadoAtual' => $estadoAtual,
        ]);
    }

    public function nova()
    {
        return view('admin/cms/noticias/formulario', [
            'noticia'                => null,
            'categorias'             => model('NoticiaCategoriaModel')->orderBy('ordem')->findAll(),
            'categoriasSelecionadas' => [],
            'tagsTexto'              => '',
            'transicoes'             => [],
        ]);
    }

    public function guardar()
    {
        if (! $this->validate('guardarNoticia')) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        $id = service('noticias')->criarRascunho($this->dadosDoFormulario(), auth()->id());

        return redirect()->to("admin/cms/noticias/editar/{$id}")
            ->with('sucesso', lang('Cms.rascunhoCriado'));
    }

    public function editar(int $id)
    {
        $noticia = model('NoticiaModel')->find($id)
            ?? throw PageNotFoundException::forPageNotFound();

        $this->exigirAutoriaOuEdicao($noticia);

        return view('admin/cms/noticias/formulario', [
            'noticia'                => $noticia,
            'categorias'             => model('NoticiaCategoriaModel')->orderBy('ordem')->findAll(),
            'categoriasSelecionadas' => $this->categoriasDe($id),
            'tagsTexto'              => $this->tagsDe($id),
            // Botões gerados pela máquina de estados (só o que é permitido).
            'transicoes'             => service('maquinaEstadosNoticia')->disponiveis($noticia->status),
        ]);
    }

    public function atualizar(int $id)
    {
        if (! $this->validate('guardarNoticia')) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        $this->exigirAutoriaOuEdicao(model('NoticiaModel')->find($id));
        service('noticias')->atualizar($id, $this->dadosDoFormulario(), auth()->id());

        return redirect()->back()->with('sucesso', lang('Cms.noticiaAtualizada'));
    }

    /** Ação única para todas as transições editoriais. */
    public function transitar(int $id, string $transicao)
    {
        try {
            service('noticias')->transitar($id, $transicao, [
                'data_agendada' => $this->request->getPost('data_agendada'),
            ]);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('erro', $e->getMessage());
        }

        return redirect()->back()->with('sucesso', lang('Cms.transicaoEfetuada'));
    }

    // ------------------------------ Internos ------------------------------

    private function dadosDoFormulario(): array
    {
        $post = $this->request->getPost([
            'titulo', 'subtitulo', 'resumo', 'conteudo',
            'meta_titulo', 'meta_descricao', 'categorias', 'tags',
        ]);

        // Checkboxes: ausentes no POST significam 0.
        $post['destaque']             = $this->request->getPost('destaque') ? 1 : 0;
        $post['fixada']               = $this->request->getPost('fixada') ? 1 : 0;
        $post['permitir_comentarios'] = $this->request->getPost('permitir_comentarios') ? 1 : 0;

        // Tags chegam como texto separado por vírgulas.
        $post['tags'] = array_filter(array_map('trim', explode(',', (string) ($post['tags'] ?? ''))));

        return $post;
    }

    /** @return int[] */
    private function categoriasDe(int $noticiaId): array
    {
        $linhas = db_connect()->table('noticias_categorias_rel')
            ->select('categoria_id')->where('noticia_id', $noticiaId)
            ->get()->getResultArray();

        return array_map('intval', array_column($linhas, 'categoria_id'));
    }

    private function tagsDe(int $noticiaId): string
    {
        $linhas = db_connect()->table('noticias_tags_rel ntr')
            ->select('nt.nome')
            ->join('noticias_tags nt', 'nt.id = ntr.tag_id')
            ->where('ntr.noticia_id', $noticiaId)
            ->get()->getResultArray();

        return implode(', ', array_column($linhas, 'nome'));
    }

    /** Jornalista só mexe no que é seu; editor mexe em tudo. */
    private function exigirAutoriaOuEdicao(?object $noticia): void
    {
        if ($noticia === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! auth()->user()->can('cms.conteudo.publicar')
            && (int) $noticia->autor_id !== (int) auth()->id()) {
            throw new AutorizacaoException(lang('Cms.naoEAutor'));
        }
    }
}
