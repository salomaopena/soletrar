<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Inscricoes;

use App\Controllers\Admin\AdminBaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * PESQUISA DE CANDIDATOS INSCRITOS.
 *
 * Filtros combináveis: texto livre (nome, nº de inscrição, BI), edição,
 * província, município, escola, categoria, classe, género, estado da
 * inscrição e intervalo de datas.
 *
 * Três saídas para os MESMOS filtros:
 *   - ecrã (paginado)
 *   - impressão (lista pronta a assinar)   → /imprimir
 *   - CSV para Excel                        → /exportar
 *
 * Todas as consultas passam pelo escopo territorial do utilizador.
 */
class CandidatosController extends AdminBaseController
{
    /** Colunas exportadas/impressas (rótulo em português). */
    private const COLUNAS = [
        'numero_inscricao' => 'N.º inscrição',
        'nome_completo'    => 'Nome do candidato',
        'genero'           => 'Género',
        'data_nascimento'  => 'Data de nascimento',
        'classe_atual'     => 'Classe',
        'escola'           => 'Escola',
        'municipio'        => 'Município',
        'provincia'        => 'Província',
        'categoria'        => 'Categoria',
        'status'           => 'Estado',
        'encarregado'      => 'Encarregado',
        'telefone'         => 'Telefone',
    ];

    public function index()
    {
        $filtros = $this->filtros();
        $model   = $this->consulta($filtros);

        return view('admin/inscricoes/candidatos', [
            'candidatos' => $model->paginate(30),
            'pager'      => $model->pager,
            'filtros'    => $filtros,
            'total'      => $this->consulta($filtros)->countAllResults(),
            'opcoes'     => $this->opcoesDosFiltros(),
        ]);
    }

    /** Versão para impressão (mesmos filtros, sem paginação). */
    public function imprimir()
    {
        $filtros = $this->filtros();

        return view('impressao/lista_candidatos', [
            'candidatos' => $this->consulta($filtros)->orderBy('c.nome_completo')->findAll(2000),
            'filtros'    => $filtros,
            'colunas'    => self::COLUNAS,
            'titulo'     => 'Lista de candidatos inscritos',
        ]);
    }

    /** Exportação CSV (mesmos filtros). */
    public function exportar()
    {
        $filtros    = $this->filtros();
        $candidatos = $this->consulta($filtros)->orderBy('c.nome_completo')->findAll(5000);

        return service('exportacao')->csv('candidatos', self::COLUNAS, $candidatos);
    }

    /** Ficha individual do candidato. */
    public function ficha(string $token)
    {
        $id = (int) id_decifrar($token, 'candidato');

        $candidato = db_connect()->table('candidatos c')
            ->select('c.*, e.nome AS escola, p.nome AS provincia, m.nome AS municipio')
            ->join('escolas e', 'e.id = c.escola_id')
            ->join('provincias p', 'p.id = c.provincia_id')
            ->join('municipios m', 'm.id = c.municipio_id', 'left')
            ->where('c.id', $id)
            ->get()->getRow() ?? throw PageNotFoundException::forPageNotFound();

        service('autorizacao')->exigirEscopo($this->escopo, $candidato);

        return view('admin/inscricoes/ficha_candidato', [
            'candidato'    => $candidato,
            'encarregados' => model('EncarregadoModel')->where('candidato_id', $id)->findAll(),
            'inscricoes'   => db_connect()->table('inscricoes i')
                ->select('i.*, ed.nome AS edicao, cat.nome AS categoria')
                ->join('edicoes_concurso ed', 'ed.id = i.edicao_id')
                ->join('categorias_competicao cat', 'cat.id = i.categoria_id', 'left')
                ->where('i.candidato_id', $id)->get()->getResult(),
        ]);
    }

    // ============================ INTERNOS ============================

    private function filtros(): array
    {
        return [
            'q'            => trim((string) $this->request->getGet('q')),
            'edicao_id'    => $this->request->getGet('edicao_id'),
            'provincia_id' => $this->request->getGet('provincia_id'),
            'municipio_id' => $this->request->getGet('municipio_id'),
            'escola_id'    => $this->request->getGet('escola_id'),
            'categoria_id' => $this->request->getGet('categoria_id'),
            'classe'       => $this->request->getGet('classe'),
            'genero'       => $this->request->getGet('genero'),
            'status'       => $this->request->getGet('status'),
            'de'           => $this->request->getGet('de'),
            'ate'          => $this->request->getGet('ate'),
        ];
    }

    /**
     * Query base: uma inscrição por linha, com candidato, escola,
     * território, categoria e encarregado principal.
     */
    private function consulta(array $f)
    {
        $model = model('InscricaoModel')
            // ATENÇÃO: `numero_inscricao` pertence a `candidatos`, não a `inscricoes`.
            ->select('inscricoes.id, inscricoes.status, inscricoes.data_inscricao,
                      c.numero_inscricao,
                      c.id AS candidato_id, c.nome_completo, c.genero, c.data_nascimento,
                      c.classe_atual, c.bi_numero,
                      e.nome AS escola, m.nome AS municipio, p.nome AS provincia,
                      cat.nome AS categoria,
                      ee.nome_completo AS encarregado, ee.telefone')
            ->join('candidatos c', 'c.id = inscricoes.candidato_id')
            ->join('escolas e', 'e.id = inscricoes.escola_id')
            ->join('provincias p', 'p.id = inscricoes.provincia_id')
            ->join('municipios m', 'm.id = c.municipio_id', 'left')
            ->join('categorias_competicao cat', 'cat.id = inscricoes.categoria_id', 'left')
            ->join('encarregados_educacao ee',
                   'ee.candidato_id = c.id AND ee.principal = 1', 'left')
            ->noEscopo($this->escopo);   // NUNCA fora do âmbito territorial

        // Texto livre: nome, nº de inscrição ou BI
        if ($f['q'] !== '') {
            $model->groupStart()
                ->like('c.nome_completo', $f['q'])
                ->orLike('c.numero_inscricao', $f['q'])
                ->orLike('c.bi_numero', $f['q'])
                ->groupEnd();
        }

        $mapa = [
            'edicao_id'    => 'inscricoes.edicao_id',
            'provincia_id' => 'inscricoes.provincia_id',
            'municipio_id' => 'c.municipio_id',
            'escola_id'    => 'inscricoes.escola_id',
            'categoria_id' => 'inscricoes.categoria_id',
            'classe'       => 'c.classe_atual',
            'genero'       => 'c.genero',
            'status'       => 'inscricoes.status',
        ];

        foreach ($mapa as $filtro => $coluna) {
            if (! empty($f[$filtro])) {
                $model->where($coluna, $f[$filtro]);
            }
        }

        if (! empty($f['de'])) {
            $model->where('inscricoes.data_inscricao >=', $f['de'] . ' 00:00:00');
        }
        if (! empty($f['ate'])) {
            $model->where('inscricoes.data_inscricao <=', $f['ate'] . ' 23:59:59');
        }

        return $model->orderBy('c.nome_completo');
    }

    private function opcoesDosFiltros(): array
    {
        $ops = static function (array $rs, string $campo = 'nome'): array {
            $o = [];
            foreach ($rs as $r) { $o[$r->id] = $r->{$campo}; }
            return $o;
        };

        // Províncias limitadas ao escopo (um coordenador não escolhe outra província)
        $provincias = model('ProvinciaModel')->orderBy('nome');
        if (! $this->escopo->eNacional() && $this->escopo->provincias !== []) {
            $provincias->whereIn('id', $this->escopo->provincias);
        }

        return [
            'edicoes'    => $ops(model('EdicaoModel')->orderBy('ano', 'DESC')->findAll()),
            'provincias' => $ops($provincias->findAll()),
            'municipios' => $ops(model('MunicipioModel')->orderBy('nome')->findAll()),
            'escolas'    => $ops(model('EscolaModel')->where('ativo', 1)->orderBy('nome')->findAll()),
            'categorias' => $ops(model('CategoriaModel')->orderBy('nome')->findAll()),
        ];
    }
}
