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
        $cats = ['' => '— Sem categoria —'];
        foreach (model('PalavraCategoriaModel')->orderBy('nome')->findAll() as $cat) {
            $cats[$cat->id] = $cat->nome;
        }

        return [
            ['nome' => 'palavra', 'rotulo' => 'Palavra', 'tipo' => 'text',
             'obrigatorio' => true, 'largura' => 5],
            ['nome' => 'silabacao', 'rotulo' => 'Silabação', 'tipo' => 'text', 'largura' => 4,
             'ajuda' => 'Ex.: pa-ra-le-le-pí-pe-do'],
            ['nome' => 'numero_silabas', 'rotulo' => 'N.º de sílabas', 'tipo' => 'number', 'largura' => 3],

            ['nome' => 'dificuldade', 'rotulo' => 'Dificuldade', 'tipo' => 'select',
             'obrigatorio' => true, 'largura' => 4,
             'opcoes' => [
                 'muito_facil'   => 'Muito fácil',
                 'facil'         => 'Fácil',
                 'media'         => 'Média',
                 'dificil'       => 'Difícil',
                 'muito_dificil' => 'Muito difícil',
             ]],
            ['nome' => 'categoria_id', 'rotulo' => 'Categoria temática', 'tipo' => 'select',
             'largura' => 4, 'opcoes' => $cats],
            ['nome' => 'classe_gramatical', 'rotulo' => 'Classe gramatical', 'tipo' => 'select',
             'largura' => 4,
             'opcoes' => [
                 'substantivo' => 'Substantivo',
                 'adjetivo'    => 'Adjetivo',
                 'verbo'       => 'Verbo',
                 'adverbio'    => 'Advérbio',
                 'pronome'     => 'Pronome',
                 'preposicao'  => 'Preposição',
                 'conjuncao'   => 'Conjunção',
                 'interjeicao' => 'Interjeição',
                 'numeral'     => 'Numeral',
                 'artigo'      => 'Artigo',
             ]],

            ['nome' => 'genero', 'rotulo' => 'Género', 'tipo' => 'select', 'largura' => 4,
             'opcoes' => [
                 'masculino'     => 'Masculino',
                 'feminino'      => 'Feminino',
                 'comum'         => 'Comum',
                 'nao_aplicavel' => 'Não aplicável',
             ]],
            ['nome' => 'nivel_minimo_classe', 'rotulo' => 'Classe mínima', 'tipo' => 'number', 'largura' => 4,
             'ajuda' => 'A partir de que classe pode sair (1 a 8).'],
            ['nome' => 'nivel_maximo_classe', 'rotulo' => 'Classe máxima', 'tipo' => 'number', 'largura' => 4,
             'ajuda' => 'Até que classe pode sair (1 a 8).'],

            ['nome' => 'definicao', 'rotulo' => 'Definição', 'tipo' => 'textarea',
             'obrigatorio' => true, 'largura' => 12, 'linhas' => 3,
             'ajuda' => 'Lida pelo pronunciador quando o candidato a pede.'],
            ['nome' => 'exemplo_uso', 'rotulo' => 'Exemplo de uso', 'tipo' => 'textarea',
             'largura' => 12, 'linhas' => 2],
            ['nome' => 'etimologia', 'rotulo' => 'Etimologia', 'tipo' => 'textarea',
             'largura' => 12, 'linhas' => 2],

            ['nome' => 'idioma_origem', 'rotulo' => 'Idioma de origem', 'tipo' => 'text', 'largura' => 4,
             'ajuda' => 'Ex.: latim, grego, quimbundo'],
            ['nome' => 'regionalismo', 'rotulo' => 'Regionalismo', 'tipo' => 'text', 'largura' => 4,
             'ajuda' => 'Ex.: Angolanismo'],
            ['nome' => 'homofonas', 'rotulo' => 'Homófonas', 'tipo' => 'text', 'largura' => 4,
             'ajuda' => 'Palavras com som igual (o júri tem de as conhecer).'],

            ['nome' => 'pronuncia_ipa', 'rotulo' => 'Pronúncia (IPA)', 'tipo' => 'text', 'largura' => 6,
             'ajuda' => 'Transcrição fonética.'],
            ['nome' => 'audio_url', 'rotulo' => 'Áudio da pronúncia (URL)', 'tipo' => 'text', 'largura' => 6,
             'ajuda' => 'Fica disponível ao pronunciador no palco.'],

            ['nome' => 'notas_pronunciador', 'rotulo' => 'Notas para o pronunciador',
             'tipo' => 'textarea', 'largura' => 12, 'linhas' => 2,
             'ajuda' => 'Cuidados de pronúncia, ambiguidades, avisos.'],

            ['nome' => 'fonte', 'rotulo' => 'Fonte', 'tipo' => 'text', 'largura' => 8,
             'ajuda' => 'Dicionário ou obra de referência.'],
            ['nome' => 'pagina_fonte', 'rotulo' => 'Página', 'tipo' => 'text', 'largura' => 4],
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
            'palavra', 'silabacao', 'numero_silabas', 'dificuldade', 'categoria_id',
            'classe_gramatical', 'genero', 'nivel_minimo_classe', 'nivel_maximo_classe',
            'definicao', 'exemplo_uso', 'etimologia', 'idioma_origem', 'regionalismo',
            'homofonas', 'pronuncia_ipa', 'audio_url', 'notas_pronunciador',
            'fonte', 'pagina_fonte',
        ]);

        // Forma normalizada (minúsculas, sem espaços) — é o que o sistema
        // sugere ao júri no palco (a decisão humana prevalece).
        $d['palavra_normalizada'] = mb_strtolower(trim($d['palavra']));

        // Intervalo de classes: por omissão, toda a escolaridade do concurso.
        $d['nivel_minimo_classe'] = (int) ($d['nivel_minimo_classe'] ?: 1);
        $d['nivel_maximo_classe'] = (int) ($d['nivel_maximo_classe'] ?: 8);

        // FK e numéricos vazios → NULL
        $d['categoria_id']   = $d['categoria_id'] ?: null;
        $d['numero_silabas'] = $d['numero_silabas'] !== '' ? (int) $d['numero_silabas'] : null;
        $d['genero']         = $d['genero'] ?: null;

        return $d;
    }
}
