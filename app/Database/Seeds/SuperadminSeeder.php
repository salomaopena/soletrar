<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * Cria o utilizador superadministrador inicial.
 *
 * Credenciais lidas do .env (nunca hard-coded):
 *   superadmin.email = admin@soletracao.ao
 *   superadmin.senha = <senha forte>
 * Se não definidas, usa valores de desenvolvimento e AVISA para trocar.
 */
class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('superadmin.email', 'admin@soletracao.ao');
        $senha = env('superadmin.senha', 'MudarEsta@123');

        $users = model(UserModel::class);

        if ($users->findByCredentials(['email' => $email]) !== null) {
            return; // já existe — idempotente
        }

        $user = new User([
            'username' => 'superadmin',
            'email'    => $email,
            'password' => $senha,
        ]);

        $users->save($user);
        $user = $users->findByCredentials(['email' => $email]);
        $user->addGroup('superadmin');

        if ($senha === 'MudarEsta@123') {
            echo "\n  ⚠  Superadmin criado com senha PADRÃO. Troque-a imediatamente!\n";
        }
    }
}
