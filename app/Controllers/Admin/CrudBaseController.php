<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Controller CRUD abstrato (DRY).
 *
 * Um CRUD administrativo simples resume-se a: model, rota, colunas da
 * listagem, campos do formulário e regras de validação. Esta classe
 * trata do resto (index/nova/editar/guardar/atualizar), pelo que cada
 * módulo passa a ser ~40 linhas.
 *
 * Para CRUDs com regras próprias (inscrições, notícias, palavras),
 * continuam a existir controllers dedicados.
 */
abstract class CrudBaseController extends AdminBaseController
{
    /** Nome do model (ex.: 'MunicipioModel'). */
    abstract protected function model(): string;

    /** Rota base (ex.: 'admin/geografia/municipios'). */
    abstract protected function rota(): string;

    /** Título do módulo (ex.: 'Municípios'). */
    abstract protected function titulo(): string;

    /** Colunas da listagem: ['campo' => 'Rótulo'|['rotulo'=>..,'tipo'=>..]] */
    abstract protected function colunas(): array;

    /** Campos do formulário (ver admin/crud/formulario.php). */
    abstract protected function campos(): array;

    /** Regras de validação. */
    abstract protected function regras(): array;

    /** Mensagem do estado vazio (opcional). */
    protected function mensagemVazio(): string
    {
        return 'Ainda não há registos.';
    }

    /** Ordenação por omissão. */
    protected function ordenarPor(): string
    {
        return 'id';
    }

    /** Gancho para ajustar os dados antes de gravar (ex.: slug, checkboxes). */
    protected function prepararDados(array $dados): array
    {
        return $dados;
    }

    /** Gancho para filtrar a listagem (ex.: escopo territorial). */
    protected function filtrarListagem($model)
    {
        return $model;
    }

    // ======================= AÇÕES =======================

    public function index()
    {
        $model = $this->filtrarListagem(model($this->model()));

        return view('admin/crud/index', [
            'titulo'   => $this->titulo(),
            'rotaBase' => $this->rota(),
            'colunas'  => $this->colunas(),
            'registos' => $model->orderBy($this->ordenarPor())->paginate(30),
            'pager'    => $model->pager,
            'vazio'    => $this->mensagemVazio(),
        ]);
    }

    public function nova()
    {
        return view('admin/crud/formulario', [
            'titulo'   => 'Adicionar · ' . $this->titulo(),
            'rotaBase' => $this->rota(),
            'registo'  => null,
            'campos'   => $this->campos(),
        ]);
    }

    public function editar(int $id)
    {
        return view('admin/crud/formulario', [
            'titulo'   => 'Editar · ' . $this->titulo(),
            'rotaBase' => $this->rota(),
            'registo'  => model($this->model())->find($id)
                ?? throw PageNotFoundException::forPageNotFound(),
            'campos'   => $this->campos(),
        ]);
    }

    public function guardar()
    {
        if (! $this->validate($this->regras())) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        model($this->model())->insert($this->prepararDados($this->recolher()));

        return redirect()->to($this->rota())->with('sucesso', 'Registo adicionado.');
    }

    public function atualizar(int $id)
    {
        if (! $this->validate($this->regras())) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        model($this->model())->update($id, $this->prepararDados($this->recolher()));

        return redirect()->to($this->rota())->with('sucesso', 'Registo atualizado.');
    }

    // ======================= INTERNOS =======================

    /** Recolhe do POST exatamente os campos declarados em campos(). */
    private function recolher(): array
    {
        $dados = [];

        foreach ($this->campos() as $c) {
            $nome = $c['nome'];

            $tipo = $c['tipo'] ?? 'text';

            if ($tipo === 'checkbox') {
                $dados[$nome] = $this->request->getPost($nome) ? 1 : 0;
                continue;
            }

            // Colunas SET: chegam como array → gravam-se separadas por vírgulas.
            if ($tipo === 'multi') {
                $valores = (array) $this->request->getPost($nome);
                $dados[$nome] = implode(',', array_filter($valores));
                continue;
            }

            $valor = $this->request->getPost($nome);

            // Campos numéricos/FK vazios → NULL (evita erro de FK com 0).
            if ($valor === '' && ($tipo === 'number' || str_ends_with($nome, '_id'))) {
                $valor = null;
            }

            $dados[$nome] = $valor;
        }

        return $dados;
    }

    /** Utilitário: transforma registos em opções [id => nome] para selects. */
    protected function opcoes(string $model, string $campo = 'nome', ?string $ordem = null): array
    {
        $registos = model($model)->orderBy($ordem ?? $campo)->findAll();
        $opcoes   = [];

        foreach ($registos as $r) {
            $opcoes[$r->id] = $r->{$campo};
        }

        return $opcoes;
    }
}
