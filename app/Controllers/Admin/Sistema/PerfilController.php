<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Sistema;

use App\Controllers\Admin\AdminBaseController;

/**
 * Perfil do próprio utilizador — dados em `perfis_utilizador` (1:1 com
 * `users` do Shield). Cria o registo se ainda não existir.
 */
class PerfilController extends AdminBaseController
{
    public function ver()
    {
        return view('admin/sistema/perfil', [
            'utilizador' => auth()->user(),
            'perfil'     => $this->perfilDoUtilizador(auth()->id()),
            'escopo'     => $this->escopo,
            'atribuicoes'=> db_connect()->table('coordenadores_atribuicao ca')
                ->select('ca.nivel, ca.ativo, p.nome AS provincia, m.nome AS municipio, e.nome AS escola')
                ->join('provincias p', 'p.id = ca.provincia_id', 'left')
                ->join('municipios m', 'm.id = ca.municipio_id', 'left')
                ->join('escolas e', 'e.id = ca.escola_id', 'left')
                ->where('ca.user_id', auth()->id())
                ->get()->getResult(),
        ]);
    }

    public function guardar()
    {
        if (! $this->validate([
            'nome_completo' => 'required|min_length[3]|max_length[180]',
            'telefone'      => 'permit_empty|telefone_ao',
            'bi_numero'     => 'permit_empty|bi_angola',
        ])) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        $dados = [
            'nome_completo' => $this->request->getPost('nome_completo'),
            'telefone'      => $this->request->getPost('telefone'),
            'genero'        => $this->request->getPost('genero') ?: null,
            'bi_numero'     => $this->request->getPost('bi_numero'),
        ];

        $model  = model('PerfilUtilizadorModel');
        $perfil = $model->where('user_id', auth()->id())->first();

        if ($perfil === null) {
            $dados['user_id'] = auth()->id();
            $model->insert($dados);
        } else {
            $model->update($perfil->id, $dados);
        }

        return redirect()->back()->with('sucesso', 'Perfil atualizado.');
    }

    private function perfilDoUtilizador(int $userId): ?object
    {
        return model('PerfilUtilizadorModel')->where('user_id', $userId)->first();
    }
}
