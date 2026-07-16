<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Geografia;

use App\Controllers\Admin\AdminBaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * CRUD de escolas (usa as views genéricas admin/crud/*).
 */
class EscolasController extends AdminBaseController
{
    private const ROTA = 'admin/geografia/escolas';

    public function index()
    {
        $model = model('EscolaModel')
            ->select('escolas.*, p.nome AS provincia, m.nome AS municipio')
            ->join('provincias p', 'p.id = escolas.provincia_id')
            ->join('municipios m', 'm.id = escolas.municipio_id');

        // Coordenadores só veem escolas do seu território.
        if (! $this->escopo->eNacional() && $this->escopo->provincias !== []) {
            $model->whereIn('escolas.provincia_id', $this->escopo->provincias);
        }

        return view('admin/crud/index', [
            'titulo'   => 'Escolas',
            'rotaBase' => self::ROTA,
            'colunas'  => [
                'nome'      => 'Escola',
                'tipo'      => 'Tipo',
                'municipio' => 'Município',
                'provincia' => 'Província',
                'ativo'     => ['rotulo' => 'Ativa', 'tipo' => 'bool'],
            ],
            'registos' => $model->orderBy('escolas.nome')->paginate(25),
            'pager'    => $model->pager,
            'vazio'    => 'Ainda não há escolas registadas no seu âmbito.',
        ]);
    }

    public function nova()
    {
        return view('admin/crud/formulario', [
            'titulo'   => 'Nova escola',
            'rotaBase' => self::ROTA,
            'registo'  => null,
            'campos'   => $this->campos(),
        ]);
    }

    public function editar(int $id)
    {
        $escola = model('EscolaModel')->find($id) ?? throw PageNotFoundException::forPageNotFound();
        service('autorizacao')->exigirEscopo($this->escopo, $escola);

        return view('admin/crud/formulario', [
            'titulo'   => 'Editar escola',
            'rotaBase' => self::ROTA,
            'registo'  => $escola,
            'campos'   => $this->campos(),
        ]);
    }

    public function guardar()
    {
        if (! $this->validate($this->regras())) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        model('EscolaModel')->insert($this->dados());

        return redirect()->to(self::ROTA)->with('sucesso', 'Escola adicionada.');
    }

    public function atualizar(int $id)
    {
        $escola = model('EscolaModel')->find($id) ?? throw PageNotFoundException::forPageNotFound();
        service('autorizacao')->exigirEscopo($this->escopo, $escola);

        if (! $this->validate($this->regras())) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        model('EscolaModel')->update($id, $this->dados());

        return redirect()->to(self::ROTA)->with('sucesso', 'Escola atualizada.');
    }

    // ------------------------------ Internos ------------------------------

    private function campos(): array
    {
        return [
            ['nome' => 'nome', 'rotulo' => 'Nome da escola', 'obrigatorio' => true, 'largura' => 12],
            ['nome' => 'tipo', 'rotulo' => 'Tipo', 'tipo' => 'select', 'obrigatorio' => true, 'largura' => 4,
             'opcoes' => ['publica' => 'Pública', 'privada' => 'Privada', 'comparticipada' => 'Comparticipada']],
            ['nome' => 'provincia_id', 'rotulo' => 'Província', 'tipo' => 'select', 'obrigatorio' => true, 'largura' => 4,
             'opcoes' => $this->opcoesProvincias()],
            ['nome' => 'municipio_id', 'rotulo' => 'Município', 'tipo' => 'select', 'obrigatorio' => true, 'largura' => 4,
             'opcoes' => $this->opcoesMunicipios()],
            ['nome' => 'subsistema', 'rotulo' => 'Subsistema', 'tipo' => 'select', 'largura' => 4,
             'opcoes' => [
                 'ensino_geral'           => 'Ensino geral',
                 'tecnico_profissional'   => 'Técnico-profissional',
                 'formacao_professores'   => 'Formação de professores',
             ]],
            ['nome' => 'nivel', 'rotulo' => 'Níveis leccionados', 'tipo' => 'multi', 'largura' => 4,
             'ajuda' => 'O concurso vai até à 8.ª classe.',
             'opcoes' => [
                 'primario' => 'Primário (1.ª–6.ª)',
                 'i_ciclo'  => 'I Ciclo (7.ª–9.ª)',
                 'ii_ciclo' => 'II Ciclo (10.ª–13.ª)',
             ]],
            ['nome' => 'numero_alunos', 'rotulo' => 'N.º de alunos', 'tipo' => 'number', 'largura' => 4],
            ['nome' => 'endereco', 'rotulo' => 'Endereço', 'tipo' => 'text', 'largura' => 12],
            ['nome' => 'latitude', 'rotulo' => 'Latitude', 'tipo' => 'text', 'largura' => 4,
             'ajuda' => 'Opcional (ex.: -8.83833)'],
            ['nome' => 'longitude', 'rotulo' => 'Longitude', 'tipo' => 'text', 'largura' => 4,
             'ajuda' => 'Opcional (ex.: 13.23444)'],
            ['nome' => 'telefone', 'rotulo' => 'Telefone', 'largura' => 4],
            ['nome' => 'email', 'rotulo' => 'E-mail', 'tipo' => 'email', 'largura' => 4],
            ['nome' => 'diretor_nome', 'rotulo' => 'Nome do diretor', 'largura' => 4],
            ['nome' => 'ativo', 'rotulo' => 'Escola ativa', 'tipo' => 'checkbox'],
        ];
    }

    private function regras(): array
    {
        return [
            'nome'         => 'required|min_length[3]|max_length[180]',
            'tipo'         => 'required|in_list[publica,privada,comparticipada]',
            'provincia_id' => 'required|is_natural_no_zero',
            'municipio_id' => 'required|is_natural_no_zero',
            'telefone'     => 'permit_empty|telefone_ao',
            'email'        => 'permit_empty|valid_email',
        ];
    }

    private function dados(): array
    {
        $d = $this->request->getPost([
            'nome', 'tipo', 'subsistema', 'provincia_id', 'municipio_id',
            'endereco', 'telefone', 'email', 'diretor_nome',
            'numero_alunos', 'latitude', 'longitude',
        ]);

        $d['ativo'] = $this->request->getPost('ativo') ? 1 : 0;

        // `nivel` é uma coluna SET: chega como array, grava-se separado por vírgulas.
        $niveis = (array) $this->request->getPost('nivel');
        $d['nivel'] = $niveis !== []
            ? implode(',', array_intersect($niveis, ['primario', 'i_ciclo', 'ii_ciclo']))
            : 'primario,i_ciclo';

        // Numéricos vazios → NULL (não 0)
        foreach (['numero_alunos', 'latitude', 'longitude'] as $campo) {
            $d[$campo] = ($d[$campo] === '' || $d[$campo] === null) ? null : $d[$campo];
        }

        return $d;
    }

    private function opcoesProvincias(): array
    {
        $p = model('ProvinciaModel')->orderBy('nome')->findAll();

        return array_column(array_map(static fn ($x) => (array) $x, $p), 'nome', 'id');
    }

    private function opcoesMunicipios(): array
    {
        $m = model('MunicipioModel')->orderBy('nome')->findAll();

        return array_column(array_map(static fn ($x) => (array) $x, $m), 'nome', 'id');
    }
}
