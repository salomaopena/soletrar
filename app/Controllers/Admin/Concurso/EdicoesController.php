<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Concurso;

use App\Controllers\Admin\AdminBaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * CRUD de edições do concurso. É o pré-requisito de dados para as
 * inscrições (o formulário público só abre com uma edição em
 * 'inscricoes_abertas' e dentro do prazo).
 */
class EdicoesController extends AdminBaseController
{
    private const ROTA = 'admin/edicoes';

    public function index()
    {
        return view('admin/crud/index', [
            'titulo'   => 'Edições do concurso',
            'rotaBase' => self::ROTA,
            'colunas'  => [
                'ano'                          => 'Ano',
                'nome'                         => 'Nome',
                'status'                       => ['rotulo' => 'Estado', 'tipo' => 'badge'],
                'data_abertura_inscricoes'     => ['rotulo' => 'Abre', 'tipo' => 'data'],
                'data_encerramento_inscricoes' => ['rotulo' => 'Fecha', 'tipo' => 'data'],
            ],
            'registos' => model('EdicaoModel')->orderBy('ano', 'DESC')->findAll(),
            'vazio'    => 'Crie a primeira edição para poder abrir inscrições.',
        ]);
    }

    public function nova()
    {
        return view('admin/crud/formulario', [
            'titulo'   => 'Nova edição',
            'rotaBase' => self::ROTA,
            'registo'  => null,
            'campos'   => $this->campos(),
        ]);
    }

    public function editar(int $id)
    {
        return view('admin/crud/formulario', [
            'titulo'   => 'Editar edição',
            'rotaBase' => self::ROTA,
            'registo'  => model('EdicaoModel')->find($id) ?? throw PageNotFoundException::forPageNotFound(),
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
        helper('texto');
        $dados['slug'] = slug_pt($dados['nome']);

        model('EdicaoModel')->insert($dados);

        return redirect()->to(self::ROTA)->with('sucesso', 'Edição criada.');
    }

    public function atualizar(int $id)
    {
        if (! $this->validate($this->regras())) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        model('EdicaoModel')->update($id, $this->dados());

        return redirect()->to(self::ROTA)->with('sucesso', 'Edição atualizada.');
    }

    private function campos(): array
    {
        return [
            ['nome' => 'nome', 'rotulo' => 'Nome da edição', 'obrigatorio' => true, 'largura' => 8,
             'ajuda' => 'Ex.: Concurso Nacional de Soletração 2026'],
            ['nome' => 'ano', 'rotulo' => 'Ano', 'tipo' => 'number', 'obrigatorio' => true, 'largura' => 4],
            ['nome' => 'tema', 'rotulo' => 'Tema', 'largura' => 12],
            ['nome' => 'descricao', 'rotulo' => 'Descrição', 'tipo' => 'textarea', 'largura' => 12],
            ['nome' => 'status', 'rotulo' => 'Estado', 'tipo' => 'select', 'obrigatorio' => true, 'largura' => 6,
             'ajuda' => 'As inscrições públicas só abrem com "inscricoes_abertas" E dentro do prazo.',
             'opcoes' => [
                 'planeamento'         => 'Planeamento',
                 'inscricoes_abertas'  => 'Inscrições abertas',
                 'inscricoes_fechadas' => 'Inscrições fechadas',
                 'em_curso'            => 'Em curso',
                 'final'               => 'Final',
                 'encerrado'           => 'Encerrado',
                 'cancelado'           => 'Cancelado',
             ]],
            ['nome' => 'data_abertura_inscricoes', 'rotulo' => 'Abertura das inscrições',
             'tipo' => 'datetime-local', 'obrigatorio' => true, 'largura' => 6],
            ['nome' => 'data_encerramento_inscricoes', 'rotulo' => 'Encerramento das inscrições',
             'tipo' => 'datetime-local', 'obrigatorio' => true, 'largura' => 6],
            ['nome' => 'data_inicio', 'rotulo' => 'Início do concurso', 'tipo' => 'date', 'largura' => 3],
            ['nome' => 'data_fim', 'rotulo' => 'Fim do concurso', 'tipo' => 'date', 'largura' => 3],
            ['nome' => 'classe_minima', 'rotulo' => 'Classe mínima', 'tipo' => 'number', 'largura' => 3],
            ['nome' => 'classe_maxima', 'rotulo' => 'Classe máxima', 'tipo' => 'number', 'largura' => 3],
            ['nome' => 'idade_minima', 'rotulo' => 'Idade mínima', 'tipo' => 'number', 'largura' => 3],
            ['nome' => 'idade_maxima', 'rotulo' => 'Idade máxima', 'tipo' => 'number', 'largura' => 3],
            ['nome' => 'regulamento_url', 'rotulo' => 'Regulamento (URL)', 'tipo' => 'text', 'largura' => 6,
             'ajuda' => 'Link do PDF do regulamento oficial.'],
            ['nome' => 'cartaz_url', 'rotulo' => 'Cartaz (URL)', 'tipo' => 'text', 'largura' => 6],
        ];
    }

    private function regras(): array
    {
        return [
            'nome'                         => 'required|min_length[5]|max_length[150]',
            'ano'                          => 'required|integer',
            'status'                       => 'required',
            'data_abertura_inscricoes'     => 'required',
            'data_encerramento_inscricoes' => 'required',
        ];
    }

    /** Converte as datas do formulário (hora de Luanda) para UTC. */
    private function dados(): array
    {
        $d = $this->request->getPost([
            'nome', 'ano', 'tema', 'descricao', 'status',
            'data_inicio', 'data_fim',
            'classe_minima', 'classe_maxima', 'idade_minima', 'idade_maxima',
            'regulamento_url', 'cartaz_url',
        ]);

        foreach (['data_abertura_inscricoes', 'data_encerramento_inscricoes'] as $campo) {
            $valor = service('dataHora')->deFormulario($this->request->getPost($campo));
            $d[$campo] = $valor?->toDateTimeString();
        }

        // Campos numéricos opcionais: vazio → null (não 0).
        foreach (['classe_minima', 'classe_maxima', 'idade_minima', 'idade_maxima'] as $campo) {
            $d[$campo] = ($d[$campo] === '' || $d[$campo] === null) ? null : (int) $d[$campo];
        }

        return $d;
    }
}
