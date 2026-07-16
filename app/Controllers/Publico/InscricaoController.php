<?php


namespace App\Controllers\Publico;

use App\Controllers\BaseController;
use RuntimeException;

/**
 * Fluxo público de inscrição de candidatos.
 *
 * DEMONSTRA a amarração de todas as camadas:
 *  - datas (edição aberta?) via DataHoraService;
 *  - província derivada da escola (RN-01 por construção — Fase 6);
 *  - validação centralizada (regras nomeadas + RegrasAngola — Fase 4);
 *  - transação + número + notificação no InscricaoService (Fase 6);
 *  - comprovativo por link com id cifrado + TTL (Fase 4).
 *
 * Rotas:
 *   GET  inscricao                      → formulario
 *   POST inscricao                      → submeter   (throttle:3,10)
 *   GET  inscricao/sucesso/(:segment)   → sucesso/$1 (token comprovativo)
 *   GET  inscricao/estado/(:segment)    → estado/$1  (acompanhamento)
 *   GET  inscricao/escolas/(:num)       → escolasPorMunicipio/$1 (AJAX)
 */
class InscricaoController extends BaseController
{
    public function formulario()
    {
        $edicao = model('EdicaoModel')->edicaoAtivaParaInscricao();

        if ($edicao === null) {
            return view('publico/inscricao/fechada');
        }

        return view('publico/inscricao/formulario', [
            'edicao'     => $edicao,
            'provincias' => model('ProvinciaModel')->orderBy('nome')->findAll(),
            'categorias' => model('CategoriaModel')->where('edicao_id', $edicao->id)->findAll(),
        ]);
    }

    public function submeter()
    {
        $edicao = model('EdicaoModel')->edicaoAtivaParaInscricao()
            ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

            
        // Validação de formulário (regras nomeadas em Config/Validation.php).
        if (! $this->validate('inscricaoPublica')) {
            return redirect()->back()->withInput()
                ->with('erros', $this->validator->getErrors());
        }

        $post = $this->request->getPost();
        

        try {
            $resultado = service('inscricoes')->inscrever(
                candidato: [
                    'nome_completo'    => $post['nome_completo'],
                    'nome_preferido'   => $post['nome_preferido'] ?? null,
                    'genero'           => $post['genero'],
                    'data_nascimento'  => $post['data_nascimento'],
                    'cedula_numero'    => $post['bi_numero'],
                    'bi_numero'        => $post['bi_numero'] ?? null,
                    'escola_id'        => (int) $post['escola_id'],
                    'classe_atual'     => (int) $post['classe_atual'],
                    'turma'            => $post['turma'] ?? null,

                    'endereco' => $post['endereco'] ?? null,
                    'telefone_contacto' => $post['telefone_contacto'] ?? null,
                    'email_contacto' => $post['email_contacto'] ?? null,
                    'tem_necessidades_especiais' => (int) ($post['tem_necessidades_especiais'] ?? 0),
                    'descricao_necessidades' => $post['descricao_necessidades'] ?? null,
                    'idioma_materno' => $post['idioma_materno'] ?? null,
                    'outros_idiomas' => $post['outros_idiomas'] ?? null,
                    'notas' => $post['notas'] ?? null,
                ],

                encarregado: [
                    'nome_completo' => $post['enc_nome_completo'],
                    'bi_numero'     => $post['enc_bi_numero'] ?? null,
                    'parentesco'    => $post['enc_parentesco'],
                    'telefone'      => $post['enc_telefone'],
                    'email'         => $post['enc_email'] ?? null,
                    'autorizou'     => $this->request->getPost('enc_autorizou') === '1',
                    'endereco'       => $post['endereco'] ?? null,
                    'profissao'     => $post['enc_profissao'] ?? null,
                ],
                edicaoId: $edicao->id,
                categoriaId: (int) $post['categoria_id'], 
            );
        } catch (RuntimeException $e) {
            // Mensagens de regra de negócio (prazo, classe, RN-02...) são traduzíveis.
            return redirect()->back()->withInput()->with('erro', $e->getMessage());
        }

        // Redireciona para o comprovativo com o ID cifrado (nunca o ID cru).
        $token = service('urlCrypt')->cifrarLinkExterno($resultado['inscricao_id'], 'comprovativo');

        return redirect()->to('inscricao/sucesso/' . $token);
    }

    public function sucesso(string $token)
    {
        $id = (int) service('urlCrypt')->decifrar($token, 'comprovativo');

        return view('publico/inscricao/sucesso', [
            'inscricao' => model('InscricaoModel')->comDetalhes()->find($id)
                ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(),
            'token'     => $token,
        ]);
    }

    /** Acompanhamento do estado (link enviado por e-mail ao encarregado). */
    public function estado(string $token)
    {
        $id = (int) service('urlCrypt')->decifrar($token, 'comprovativo');

        return view('publico/inscricao/estado', [
            'inscricao' => model('InscricaoModel')->comDetalhes()->find($id)
                ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(),
        ]);
    }

    /** Endpoint AJAX: municípios de uma província (dropdowns dependentes). */
    public function municipiosPorProvincia(int $provinciaId)
    {
        return $this->response->setJSON(
            model('MunicipioModel')
                ->select('id, nome')
                ->where('provincia_id', $provinciaId)
                ->where('ativo', 1)
                ->orderBy('nome')
                ->findAll()
        );
    }

    /** Endpoint AJAX: escolas de um município (dependência de dropdowns). */
    public function escolasPorMunicipio(int $municipioId)
    {
        return $this->response->setJSON(
            model('EscolaModel')
                ->select('id, nome')
                ->where('municipio_id', $municipioId)
                ->where('ativo', 1)
                ->orderBy('nome')
                ->findAll()
        );
    }
}
