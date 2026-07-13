<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Sistema;

use App\Controllers\Admin\AdminBaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * Gestão de utilizadores (Shield) + perfil + ÂMBITO TERRITORIAL.
 *
 * LÓGICA IMPORTANTE: criar uma conta de coordenador NÃO basta — sem uma
 * atribuição em `coordenadores_atribuicao` o EscopoService devolve âmbito
 * vazio e a pessoa não vê nada (falha fechada, por segurança). Por isso o
 * formulário cria a conta, o perfil E a atribuição numa só operação.
 */
class UtilizadoresController extends AdminBaseController
{
    /** Grupos que exigem território. */
    private const GRUPOS_TERRITORIAIS = [
        'coord_provincial' => 'provincial',
        'coord_municipal'  => 'municipal',
        'coord_escolar'    => 'escolar',
        'professor'        => 'escolar',
    ];

    public function index()
    {
        $utilizadores = db_connect()->table('users u')
            ->select('u.id, u.username, u.active, u.created_at,
                      ai.secret AS email,
                      pu.nome_completo, pu.telefone,
                      GROUP_CONCAT(DISTINCT agu.group) AS grupos')
            ->join('auth_identities ai', "ai.user_id = u.id AND ai.type = 'email_password'", 'left')
            ->join('perfis_utilizador pu', 'pu.user_id = u.id', 'left')
            ->join('auth_groups_users agu', 'agu.user_id = u.id', 'left')
            ->groupBy('u.id, u.username, u.active, u.created_at, ai.secret, pu.nome_completo, pu.telefone')
            ->orderBy('u.id')
            ->get()->getResult();

        return view('admin/sistema/utilizadores', ['utilizadores' => $utilizadores]);
    }

    public function nova()
    {
        return view('admin/sistema/utilizador_form', [
            'utilizador' => null,
            'grupos'     => $this->gruposDisponiveis(),
            'provincias' => $this->opcoes('ProvinciaModel'),
            'municipios' => $this->opcoes('MunicipioModel'),
            'escolas'    => $this->opcoes('EscolaModel'),
        ]);
    }

    /** Cria conta + perfil + atribuição territorial numa transação. */
    public function guardar()
    {
        if (! $this->validate([
            'username'      => 'required|min_length[3]|max_length[30]|is_unique[users.username]',
            'email'         => 'required|valid_email|is_unique[auth_identities.secret]',
            'password'      => 'required|min_length[8]',
            'nome_completo' => 'required|min_length[3]',
            'grupo'         => 'required',
            'telefone'      => 'permit_empty|telefone_ao',
        ])) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        $grupo = (string) $this->request->getPost('grupo');
        $db    = db_connect();

        $db->transException(true)->transStart();

        /** @var UserModel $users */
        $users = model(UserModel::class);

        $user = new User([
            'username' => $this->request->getPost('username'),
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ]);
        $users->save($user);

        $novo = $users->findById($users->getInsertID());
        $novo->addGroup($grupo);

        // Perfil (perfis_utilizador)
        model('PerfilUtilizadorModel')->insert([
            'user_id'       => $novo->id,
            'nome_completo' => $this->request->getPost('nome_completo'),
            'telefone'      => $this->request->getPost('telefone'),
            'genero'        => $this->request->getPost('genero') ?: null,
        ]);

        // Atribuição territorial — sem isto, um coordenador não vê NADA.
        if (isset(self::GRUPOS_TERRITORIAIS[$grupo])) {
            model('CoordenadorAtribuicaoModel')->insert([
                'user_id'      => $novo->id,
                'nivel'        => self::GRUPOS_TERRITORIAIS[$grupo],
                'provincia_id' => $this->request->getPost('provincia_id') ?: null,
                'municipio_id' => $this->request->getPost('municipio_id') ?: null,
                'escola_id'    => $this->request->getPost('escola_id') ?: null,
                'ativo'        => 1,
                'data_inicio'  => date('Y-m-d'),
            ]);
        } elseif (in_array($grupo, ['coord_nacional', 'superadmin'], true)) {
            model('CoordenadorAtribuicaoModel')->insert([
                'user_id'     => $novo->id,
                'nivel'       => 'nacional',
                'ativo'       => 1,
                'data_inicio' => date('Y-m-d'),
            ]);
        }

        $db->transComplete();

        return redirect()->to('admin/sistema/utilizadores')
            ->with('sucesso', 'Utilizador criado com conta, perfil e âmbito territorial.');
    }

    /** Ativar/desativar conta. */
    public function alternarEstado(int $id)
    {
        $users = model(UserModel::class);
        $user  = $users->findById($id) ?? throw PageNotFoundException::forPageNotFound();

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('erro', 'Não pode desativar a sua própria conta.');
        }

        $user->active = $user->active ? 0 : 1;
        $users->save($user);

        return redirect()->back()->with('sucesso', 'Estado da conta alterado.');
    }

    // ------------------------------ Internos ------------------------------

    private function gruposDisponiveis(): array
    {
        $grupos = config('AuthGroups')->groups;
        $lista  = [];

        foreach ($grupos as $chave => $g) {
            $lista[$chave] = $g['title'];
        }

        // Só um superadmin pode criar outro superadmin.
        if (! auth()->user()->inGroup('superadmin')) {
            unset($lista['superadmin']);
        }

        return $lista;
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
