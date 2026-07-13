<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Inscricoes;

use App\Controllers\Admin\AdminBaseController;
use App\Exceptions\AutorizacaoException;

/**
 * EXEMPLO DE REFERÊNCIA (Fase 4): CRUD administrativo que demonstra
 * o uso integrado de todas as camadas de segurança:
 *
 *  - IDs cifrados na URL (nunca expostos)      → id_decifrar()/rota_segura()
 *  - Permissão Shield                          → filtro permission: nas rotas
 *  - Escopo territorial                        → $this->escopo + AutorizacaoService
 *  - Validação centralizada                    → regras nomeadas + RegrasAngola
 *  - Auditoria                                 → trait Auditavel no model + service
 *
 * Rotas (app/Config/Rotas/admin.php):
 *   GET  admin/inscricoes                    → index
 *   GET  admin/inscricoes/ver/(:segment)     → ver($token)
 *   POST admin/inscricoes/validar/(:segment) → validar($token)
 *   POST admin/inscricoes/rejeitar/(:segment)→ rejeitar($token)
 */
class InscricoesController extends AdminBaseController
{
    /** Listagem — já filtrada pelo escopo territorial do utilizador. */
    public function index()
    {
        $estadoAtual = $this->request->getGet('status') ?: 'pendente';

        $model = model('InscricaoModel');

        $inscricoes = $model
            ->comCandidatoEEscola()          // join de leitura
            ->noEscopo($this->escopo)        // NUNCA listar fora do escopo
            ->where('inscricoes.status', $estadoAtual)
            ->orderBy('inscricoes.data_inscricao', 'DESC')
            ->paginate(25);

        return view('admin/inscricoes/index', [
            'inscricoes'  => $inscricoes,
            'pager'       => $model->pager,
            'contadores'  => $this->contadoresPorEstado(),
            'estadoAtual' => $estadoAtual,
            // Na view, cada linha gera o link com:
            //   rota_segura('admin/inscricoes/ver', $i->id, 'inscricao')
        ]);
    }

    /** Contagem por estado, respeitando o escopo territorial (barra de filtros). */
    private function contadoresPorEstado(): array
    {
        $contadores = [];

        foreach (['pendente', 'validada', 'rejeitada'] as $estado) {
            $contadores[$estado] = model('InscricaoModel')
                ->noEscopo($this->escopo)
                ->where('inscricoes.status', $estado)
                ->countAllResults();
        }

        return $contadores;
    }

    /** Formulário de inscrição assistida (coordenador escolar/professor). */
    public function nova()
    {
        $edicao = model('EdicaoModel')->edicaoAtivaParaInscricao();

        return view('admin/inscricoes/formulario', [
            'edicao'     => $edicao,
            'provincias' => model('ProvinciaModel')->orderBy('nome')->findAll(),
            'categorias' => $edicao
                ? model('CategoriaModel')->where('edicao_id', $edicao->id)->findAll()
                : [],
        ]);
    }

    /** Guarda a inscrição assistida (reutiliza o MESMO service do público). */
    public function guardar()
    {
        $edicao = model('EdicaoModel')->edicaoAtivaParaInscricao();

        if ($edicao === null) {
            return redirect()->back()->with('erro', lang('Concurso.inscricoesFechadas'));
        }

        if (! $this->validate('inscricaoPublica')) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        try {
            service('inscricoes')->inscrever(
                candidato: [
                    'nome_completo'   => $post['nome_completo'],
                    'nome_preferido'  => $post['nome_preferido'] ?? null,
                    'genero'          => $post['genero'],
                    'data_nascimento' => $post['data_nascimento'],
                    'escola_id'       => (int) $post['escola_id'],
                    'classe_atual'    => (int) $post['classe_atual'],
                    'turma'           => $post['turma'] ?? null,
                ],
                encarregado: [
                    'nome_completo' => $post['enc_nome_completo'],
                    'parentesco'    => $post['enc_parentesco'],
                    'telefone'      => $post['enc_telefone'],
                    'email'         => $post['enc_email'] ?? null,
                    'autorizou'     => true,
                ],
                edicaoId: $edicao->id,
                categoriaId: (int) $post['categoria_id'],
            );
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('erro', $e->getMessage());
        }

        return redirect()->to('admin/inscricoes')->with('sucesso', 'Inscrição registada com sucesso.');
    }

    /** Detalhe — o parâmetro chega CIFRADO da URL. */
    public function ver(string $token)
    {
        // 1. Decifrar (token inválido/expirado → TokenInvalidoException → 404)
        $id = (int) id_decifrar($token, 'inscricao');

        // 2. Carregar
        $inscricao = model('InscricaoModel')->comDetalhes()->find($id)
            ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        // 3. Escopo: pode ver ESTA inscrição? (403 + auditoria se não)
        service('autorizacao')->exigirEscopo($this->escopo, $inscricao);

        return view('admin/inscricoes/ver', ['inscricao' => $inscricao]);
    }

    /** Validação de uma inscrição pendente. */
    public function validar(string $token)
    {
        $id = (int) id_decifrar($token, 'inscricao');

        try {
            // Toda a regra de negócio vive no service (transação,
            // mudança de estado, notificação ao encarregado, auditoria).
            service('inscricoes')->validar($id, $this->escopo, auth()->id());
        } catch (AutorizacaoException $e) {
            return redirect()->back()->with('erro', $e->getMessage());
        }

        return redirect()->to('admin/inscricoes')
            ->with('sucesso', lang('Concurso.inscricaoValidada'));
    }

    /** Rejeição com motivo obrigatório (validação centralizada). */
    public function rejeitar(string $token)
    {
        $id = (int) id_decifrar($token, 'inscricao');

        // Regras nomeadas vivem em Config/Validation.php ($rejeitarInscricao),
        // reutilizáveis por qualquer controller ou comando CLI.
        if (! $this->validate('rejeitarInscricao')) {
            return redirect()->back()->withInput()
                ->with('erros', $this->validator->getErrors());
        }

        service('inscricoes')->rejeitar(
            inscricaoId: $id,
            escopo: $this->escopo,
            motivo: $this->request->getPost('motivo_rejeicao'),
            porUserId: auth()->id(),
        );

        return redirect()->to('admin/inscricoes')
            ->with('sucesso', lang('Concurso.inscricaoRejeitada'));
    }
}
