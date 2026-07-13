<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Palavras;

use App\Controllers\Admin\AdminBaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * CRUD do banco de palavras.
 *
 * RN-06: só palavras VALIDADAS entram no pool de um evento. A validação
 * é um passo à parte, com permissão própria (palavras.validar).
 */
class PalavrasController extends AdminBaseController
{
    private const ROTA = 'admin/palavras';

    public function index()
    {
        $estado = $this->request->getGet('estado') ?: 'todas';
        $termo  = trim((string) $this->request->getGet('q'));

        $model = model('PalavraModel');

        if ($estado === 'por_validar') {
            $model->where('validada', 0);
        } elseif ($estado === 'validadas') {
            $model->where('validada', 1);
        }

        if ($termo !== '') {
            $model->like('palavra', $termo);
        }

        $db = db_connect();

        return view('admin/palavras/index', [
            'palavras'    => $model->orderBy('palavra')->paginate(30),
            'pager'       => $model->pager,
            'estadoAtual' => $estado,
            'termo'       => $termo,
            'contadores'  => [
                'todas'       => $db->table('palavras')->where('deleted_at', null)->countAllResults(),
                'por_validar' => $db->table('palavras')->where('validada', 0)
                                    ->where('deleted_at', null)->countAllResults(),
                'validadas'   => $db->table('palavras')->where('validada', 1)
                                    ->where('deleted_at', null)->countAllResults(),
            ],
        ]);
    }

    /** Validação em massa (várias palavras de uma vez). */
    public function validarVarias()
    {
        $ids = (array) $this->request->getPost('ids');

        if ($ids === []) {
            return redirect()->back()->with('erro', 'Selecione pelo menos uma palavra.');
        }

        model('PalavraModel')
            ->whereIn('id', array_map('intval', $ids))
            ->set(['validada' => 1, 'validada_por' => auth()->id()])
            ->update();

        return redirect()->back()
            ->with('sucesso', count($ids) . ' palavra(s) validada(s) e disponível(is) para concurso.');
    }

    public function nova()
    {
        return view('admin/crud/formulario', [
            'titulo'   => 'Nova palavra',
            'rotaBase' => self::ROTA,
            'registo'  => null,
            'campos'   => $this->campos(),
        ]);
    }

    public function editar(int $id)
    {
        return view('admin/crud/formulario', [
            'titulo'   => 'Editar palavra',
            'rotaBase' => self::ROTA,
            'registo'  => model('PalavraModel')->find($id) ?? throw PageNotFoundException::forPageNotFound(),
            'campos'   => $this->campos(),
        ]);
    }

    public function guardar()
    {
        if (! $this->validate($this->regras())) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        $dados = $this->dados();
        $dados['criada_por'] = auth()->id();

        model('PalavraModel')->insert($dados);

        return redirect()->to(self::ROTA)->with('sucesso', 'Palavra adicionada (aguarda validação).');
    }

    public function atualizar(int $id)
    {
        if (! $this->validate($this->regras())) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        model('PalavraModel')->update($id, $this->dados());

        return redirect()->to(self::ROTA)->with('sucesso', 'Palavra atualizada.');
    }

    /** Validação pedagógica (permissão palavras.validar). */
    public function validar(int $id)
    {
        model('PalavraModel')->update($id, [
            'validada'     => 1,
            'validada_por' => auth()->id(),
        ]);

        return redirect()->back()->with('sucesso', 'Palavra validada e disponível para concursos.');
    }

    /** Retirar a validação (volta a ficar indisponível para concursos). */
    public function invalidar(int $id)
    {
        model('PalavraModel')->update($id, ['validada' => 0, 'validada_por' => null]);

        return redirect()->back()->with('sucesso', 'Validação retirada.');
    }

    private function campos(): array
    {
        return [
            ['nome' => 'palavra', 'rotulo' => 'Palavra', 'obrigatorio' => true, 'largura' => 6],
            ['nome' => 'silabacao', 'rotulo' => 'Silabação', 'largura' => 6,
             'ajuda' => 'Ex.: pa-ra-le-le-pí-pe-do'],
            ['nome' => 'dificuldade', 'rotulo' => 'Dificuldade', 'tipo' => 'select', 'obrigatorio' => true, 'largura' => 4,
             'opcoes' => [
                 'muito_facil'   => 'Muito fácil',
                 'facil'         => 'Fácil',
                 'media'         => 'Média',
                 'dificil'       => 'Difícil',
                 'muito_dificil' => 'Muito difícil',
             ]],
            ['nome' => 'nivel_minimo_classe', 'rotulo' => 'Classe mínima', 'tipo' => 'number', 'largura' => 4],
            ['nome' => 'nivel_maximo_classe', 'rotulo' => 'Classe máxima', 'tipo' => 'number', 'largura' => 4],
            ['nome' => 'classe_gramatical', 'rotulo' => 'Classe gramatical', 'largura' => 6,
             'ajuda' => 'substantivo, adjetivo, verbo...'],
            ['nome' => 'idioma_origem', 'rotulo' => 'Idioma de origem', 'largura' => 6],
            ['nome' => 'definicao', 'rotulo' => 'Definição', 'tipo' => 'textarea', 'obrigatorio' => true, 'largura' => 12, 'linhas' => 3],
            ['nome' => 'exemplo_uso', 'rotulo' => 'Exemplo de uso', 'tipo' => 'textarea', 'largura' => 12, 'linhas' => 2],
            ['nome' => 'etimologia', 'rotulo' => 'Etimologia', 'tipo' => 'textarea', 'largura' => 12, 'linhas' => 2],
            ['nome' => 'notas_pronunciador', 'rotulo' => 'Notas para o pronunciador', 'tipo' => 'textarea', 'largura' => 12, 'linhas' => 2,
             'ajuda' => 'Homófonas, cuidados de pronúncia, regionalismos.'],
        ];
    }

    private function regras(): array
    {
        return [
            'palavra'     => 'required|min_length[2]|max_length[100]',
            'dificuldade' => 'required|in_list[muito_facil,facil,media,dificil,muito_dificil]',
            'definicao'   => 'required|min_length[5]',
        ];
    }

    private function dados(): array
    {
        $d = $this->request->getPost([
            'palavra', 'silabacao', 'dificuldade', 'classe_gramatical',
            'idioma_origem', 'definicao', 'exemplo_uso', 'etimologia',
            'notas_pronunciador', 'nivel_minimo_classe', 'nivel_maximo_classe',
        ]);

        // Forma normalizada (minúsculas, sem espaços) para a comparação
        // automática que se sugere ao júri no palco.
        $d['palavra_normalizada'] = mb_strtolower(trim($d['palavra']));
        $d['nivel_minimo_classe'] = (int) ($d['nivel_minimo_classe'] ?: 1);
        $d['nivel_maximo_classe'] = (int) ($d['nivel_maximo_classe'] ?: 8);

        return $d;
    }
}
