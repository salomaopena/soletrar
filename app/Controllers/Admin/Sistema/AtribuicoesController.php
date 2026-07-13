<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Sistema;

use App\Controllers\Admin\AdminBaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * ATRIBUIÇÕES TERRITORIAIS (coordenadores_atribuicao).
 *
 * É a tabela que dá PODER REAL a uma conta: o EscopoService lê daqui o
 * território de cada utilizador. Um coordenador sem atribuição ativa vê
 * ZERO registos (falha fechada, por segurança) — por isso esta página
 * existe: para atribuir/retirar território a contas já criadas.
 *
 * Níveis:
 *   nacional   → vê tudo (não precisa de província)
 *   provincial → limitado a uma província
 *   municipal  → limitado a um município
 *   escolar    → limitado a uma escola
 */
class AtribuicoesController extends AdminBaseController
{
    /** Atribuições de um utilizador. */
    public function utilizador(int $userId)
    {
        $utilizador = db_connect()->table('users u')
            ->select('u.id, u.username, pu.nome_completo')
            ->join('perfis_utilizador pu', 'pu.user_id = u.id', 'left')
            ->where('u.id', $userId)
            ->get()->getRow() ?? throw PageNotFoundException::forPageNotFound();

        $grupos = db_connect()->table('auth_groups_users')
            ->select('group')->where('user_id', $userId)
            ->get()->getResultArray();

        return view('admin/sistema/atribuicoes', [
            'utilizador'  => $utilizador,
            'grupos'      => array_column($grupos, 'group'),
            'atribuicoes' => db_connect()->table('coordenadores_atribuicao ca')
                ->select('ca.*, p.nome AS provincia, m.nome AS municipio, e.nome AS escola')
                ->join('provincias p', 'p.id = ca.provincia_id', 'left')
                ->join('municipios m', 'm.id = ca.municipio_id', 'left')
                ->join('escolas e', 'e.id = ca.escola_id', 'left')
                ->where('ca.user_id', $userId)
                ->orderBy('ca.ativo', 'DESC')
                ->get()->getResult(),
            'provincias' => $this->opcoes('ProvinciaModel'),
            'municipios' => $this->opcoes('MunicipioModel'),
            'escolas'    => $this->opcoes('EscolaModel'),
        ]);
    }

    public function guardar(int $userId)
    {
        $nivel = (string) $this->request->getPost('nivel');

        if (! $this->validate([
            'nivel' => 'required|in_list[nacional,provincial,municipal,escolar]',
        ])) {
            return redirect()->back()->with('erros', $this->validator->getErrors());
        }

        // Cada nível exige o seu território (exceto o nacional).
        $exigido = [
            'provincial' => 'provincia_id',
            'municipal'  => 'municipio_id',
            'escolar'    => 'escola_id',
        ][$nivel] ?? null;

        if ($exigido !== null && ! $this->request->getPost($exigido)) {
            return redirect()->back()->with('erro',
                'O nível "' . $nivel . '" exige que indique o território correspondente.');
        }

        model('CoordenadorAtribuicaoModel')->insert([
            'user_id'      => $userId,
            'nivel'        => $nivel,
            'provincia_id' => $this->request->getPost('provincia_id') ?: null,
            'municipio_id' => $this->request->getPost('municipio_id') ?: null,
            'escola_id'    => $this->request->getPost('escola_id') ?: null,
            'ativo'        => 1,
            'data_inicio'  => $this->request->getPost('data_inicio') ?: date('Y-m-d'),
            'data_fim'     => $this->request->getPost('data_fim') ?: null,
        ]);

        return redirect()->back()->with('sucesso', 'Atribuição criada. O utilizador já vê este território.');
    }

    /** Ativar/desativar uma atribuição (o histórico não se apaga). */
    public function alternar(int $userId, int $atribuicaoId)
    {
        $a = model('CoordenadorAtribuicaoModel')->find($atribuicaoId)
            ?? throw PageNotFoundException::forPageNotFound();

        model('CoordenadorAtribuicaoModel')->update($atribuicaoId, ['ativo' => $a->ativo ? 0 : 1]);

        return redirect()->back()->with('sucesso', 'Atribuição atualizada.');
    }

    private function opcoes(string $model): array
    {
        $o = [];
        foreach (model($model)->orderBy('nome')->findAll() as $r) {
            $o[$r->id] = $r->nome;
        }
        return $o;
    }
}
