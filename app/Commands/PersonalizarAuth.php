<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Aponta o Config/Auth.php (Shield) para as views personalizadas do
 * projeto — sem precisar de editar o ficheiro à mão.
 *
 *   php spark app:personalizar-auth
 *
 * PORQUÊ ESTE COMANDO EXISTE: as chaves exatas do array $views variam
 * ligeiramente entre versões do Shield, e sem o pacote instalado à
 * frente não há como as adivinhar com 100% de certeza por escrito.
 * Este comando corre DENTRO do seu ambiente, contra o SEU ficheiro
 * real — não precisa de adivinhar nada, encontra as chaves a sério.
 *
 * Faz uma cópia de segurança (Auth.php.bak) antes de qualquer alteração.
 */
class PersonalizarAuth extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:personalizar-auth';
    protected $description = 'Liga o Config/Auth.php às views personalizadas (login, forgot, reset) e desliga o autorregisto.';

    public function run(array $params): void
    {
        CLI::write('PERSONALIZAR TELAS DE AUTENTICAÇÃO (SHIELD)', 'yellow');
        CLI::write(str_repeat('=', 55));

        $this->verificarViewsNecessarias();

        $caminho = APPPATH . 'Config/Auth.php';

        if (! is_file($caminho)) {
            CLI::newLine();
            CLI::error('Não encontrei app/Config/Auth.php.');
            CLI::write('Publique-o primeiro com:', 'yellow');
            CLI::write('   php spark shield:publish', 'green');
            return;
        }

        $original = file_get_contents($caminho);
        $linhas   = explode("\n", $original);
        $relatorio = [];

        // [padrão da CHAVE (regex, case-insensitive), excluir linha se
        //  contiver isto, novo valor para a view]
        $regrasViews = [
            ['/^\s*[\'"]login[\'"]\s*=>/i',                          null,             'auth/login'],
            ['/^\s*[\'"][^\'"]*forgot[^\'"]*email[^\'"]*[\'"]\s*=>/i', null,             'emails/auth/forgot_password_email'],
            ['/^\s*[\'"][^\'"]*forgot[^\'"]*[\'"]\s*=>/i',            'email',          'auth/forgot_password_form'],
            ['/^\s*[\'"][^\'"]*reset[^\'"]*[\'"]\s*=>/i',             'email|success',  'auth/reset_password_form'],
        ];

        foreach ($linhas as $i => $linha) {
            foreach ($regrasViews as [$padrao, $excluirSe, $novoValor]) {
                if (! preg_match($padrao, $linha)) {
                    continue;
                }
                if ($excluirSe !== null && preg_match('/' . $excluirSe . '/i', $linha)) {
                    continue;
                }

                $nova = preg_replace("/=>\s*['\"][^'\"]*['\"]/", "=> '{$novoValor}'", $linha, 1);

                if ($nova !== null && $nova !== $linha) {
                    $relatorio[] = ['antes' => trim($linha), 'depois' => trim($nova)];
                    $linhas[$i]  = $nova;
                }

                break; // uma regra por linha chega
            }
        }

        // $allowRegistration => false (as contas criam-se por Admin → Utilizadores)
        foreach ($linhas as $i => $linha) {
            if (preg_match('/public\s+bool\s+\$allowRegistration\s*=\s*(true|false)\s*;/', $linha, $m)
                && $m[1] !== 'false') {
                $nova = preg_replace('/=\s*(true|false)\s*;/', '= false;', $linha);
                $relatorio[] = ['antes' => trim($linha), 'depois' => trim($nova)];
                $linhas[$i]  = $nova;
            }
        }

        CLI::newLine();

        if ($relatorio === []) {
            CLI::write('⚠ Nenhuma linha correspondeu às chaves esperadas.', 'yellow');
            CLI::write('  Nada foi alterado. Abra app/Config/Auth.php e mande a', 'yellow');
            CLI::write('  secção "public array $views = [...]" para ajuste manual.', 'yellow');
            return;
        }

        file_put_contents($caminho . '.bak', $original);
        file_put_contents($caminho, implode("\n", $linhas));

        CLI::write('✓ Config/Auth.php atualizado.', 'green');
        CLI::write('  Cópia de segurança gravada em Config/Auth.php.bak', 'green');
        CLI::newLine();
        CLI::write('Alterações feitas:', 'cyan');

        foreach ($relatorio as $r) {
            CLI::write('  - ' . $r['antes'], 'yellow');
            CLI::write('    → ' . $r['depois'], 'green');
        }

        CLI::newLine();
        CLI::write('Agora corra: php spark cache:clear   e visite /login', 'cyan');
    }

    /** Confirma que os ficheiros de view necessários já foram copiados. */
    private function verificarViewsNecessarias(): void
    {
        $necessarias = [
            'layouts/auth',
            'auth/login',
            'auth/forgot_password_form',
            'auth/reset_password_form',
            'emails/auth/forgot_password_email',
        ];

        $emFalta = [];
        foreach ($necessarias as $v) {
            if (! is_file(APPPATH . 'Views/' . $v . '.php')) {
                $emFalta[] = $v;
            }
        }

        if ($emFalta === []) {
            CLI::write('✓ Todas as views necessárias já existem.', 'green');
            return;
        }

        CLI::write('⚠ Faltam estas views (copie-as antes de continuar):', 'yellow');
        foreach ($emFalta as $v) {
            CLI::write('   - app/Views/' . $v . '.php', 'yellow');
        }
        CLI::newLine();
    }
}
