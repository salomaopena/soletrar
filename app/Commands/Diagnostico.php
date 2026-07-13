<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Model;
use Closure;
use Throwable;

/**
 * Diagnóstico do projeto — apanha os erros antes do utilizador.
 *
 *   php spark app:diagnostico
 *
 * Verifica:
 *   1. Sintaxe PHP de todos os ficheiros (php -l)
 *   2. Ligação à base de dados
 *   3. Models: a tabela e os allowedFields existem MESMO na BD
 *   4. Views chamadas por view() que não existem em ficheiro
 *   5. Services invocados por service() que não resolvem
 *   6. Comentários de linha que fecham o bloco PHP
 */
class Diagnostico extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'app:diagnostico';
    protected $description = 'Verifica sintaxe, models vs base de dados, views, services e erros comuns.';

    private int $problemas = 0;

    public function run(array $params): void
    {
        CLI::write('DIAGNÓSTICO DO PROJETO', 'yellow');
        CLI::write(str_repeat('=', 60));

        $this->verificarSintaxe();
        $this->verificarBaseDeDados();
        $this->verificarModels();
        $this->verificarViews();
        $this->verificarServices();
        $this->verificarComentarios();

        CLI::write(str_repeat('=', 60));

        if ($this->problemas === 0) {
            CLI::write('✓ Nenhum problema encontrado.', 'green');
        } else {
            CLI::write("✗ {$this->problemas} problema(s) encontrado(s).", 'red');
        }
    }

    // ==================================================================

    private function verificarSintaxe(): void
    {
        CLI::write("\n1. Sintaxe PHP", 'cyan');
        $erros = 0;

        foreach ($this->ficheirosPhp() as $ficheiro) {
            $saida  = [];
            $codigo = 0;
            exec('php -l ' . escapeshellarg($ficheiro) . ' 2>&1', $saida, $codigo);

            if ($codigo !== 0) {
                CLI::write('   ✗ ' . $this->relativo($ficheiro), 'red');
                CLI::write('     ' . implode(' ', $saida));
                $erros++;
            }
        }

        $this->problemas += $erros;
        $erros === 0 && CLI::write('   ✓ Todos os ficheiros compilam.', 'green');
    }

    private function verificarBaseDeDados(): void
    {
        CLI::write("\n2. Base de dados", 'cyan');

        try {
            $tabelas = db_connect()->listTables();
            CLI::write('   ✓ Ligação OK — ' . count($tabelas) . ' tabela(s).', 'green');
        } catch (Throwable $e) {
            CLI::write('   ✗ Sem ligação: ' . $e->getMessage(), 'red');
            $this->problemas++;
        }
    }

    /**
     * Model vs BASE DE DADOS REAL — o teste que mais falhas apanha.
     *
     * NOTA: as propriedades são lidas com um Closure ligado ao objeto
     * (lê $this->table de DENTRO da classe). A leitura por Reflection
     * mostrou-se pouco fiável entre versões e dava allowedFields vazio
     * em models que na verdade os tinham.
     */
    private function verificarModels(): void
    {
        CLI::write("\n3. Models vs base de dados", 'cyan');

        $db    = db_connect();
        $erros = 0;
        $lidos = 0;

        foreach (glob(APPPATH . 'Models/*.php') as $ficheiro) {
            $classe = 'App\\Models\\' . basename($ficheiro, '.php');

            if (! class_exists($classe)) {
                CLI::write("   ✗ {$classe}: a classe não carrega (nome do ficheiro?).", 'red');
                $erros++;
                continue;
            }

            try {
                $model = new $classe();
            } catch (Throwable $e) {
                CLI::write("   ✗ {$classe}: não instancia — " . $e->getMessage(), 'red');
                $erros++;
                continue;
            }

            if (! $model instanceof Model) {
                continue;   // não é um Model do CI4
            }

            // Leitura fiável das propriedades protegidas:
            $ler = Closure::bind(
                static fn (Model $m): array => [
                    'tabela' => $m->table,
                    'campos' => $m->allowedFields,
                ],
                null,
                Model::class
            );

            ['tabela' => $tabela, 'campos' => $campos] = $ler($model);
            $lidos++;

            if (! $db->tableExists($tabela)) {
                CLI::write("   ✗ {$classe}: a tabela '{$tabela}' NÃO existe na BD.", 'red');
                $erros++;
                continue;
            }

            if ($campos === []) {
                CLI::write("   ✗ {$classe}: allowedFields VAZIO (insert vai falhar).", 'red');
                $erros++;
                continue;
            }

            $colunas = $db->getFieldNames($tabela);
            $maus    = array_diff($campos, $colunas);

            if ($maus !== []) {
                CLI::write("   ✗ {$classe} ({$tabela}): colunas inexistentes → "
                    . implode(', ', $maus), 'red');
                $erros++;
            }
        }

        $this->problemas += $erros;
        $erros === 0 && CLI::write("   ✓ {$lidos} model(s) batem certo com a BD.", 'green');
    }

    private function verificarViews(): void
    {
        CLI::write("\n4. Views chamadas por view()", 'cyan');
        $erros = 0;

        foreach ($this->ficheirosPhp() as $ficheiro) {
            $codigo = file_get_contents($ficheiro);

            if (! preg_match_all("/view\(\s*'([a-z0-9_\/]+)'/i", $codigo, $m)) {
                continue;
            }

            foreach ($m[1] as $vista) {
                if (! is_file(APPPATH . 'Views/' . $vista . '.php')) {
                    CLI::write("   ✗ view('{$vista}') não existe — usada em "
                        . $this->relativo($ficheiro), 'red');
                    $erros++;
                }
            }
        }

        $this->problemas += $erros;
        $erros === 0 && CLI::write('   ✓ Todas as views existem.', 'green');
    }

    /**
     * Em vez de comparar com uma lista de nomes conhecidos (que nunca
     * está completa — falhava com os services do Shield e do próprio
     * framework), TENTA resolver cada service. Se resolve, existe.
     */
    private function verificarServices(): void
    {
        CLI::write("\n5. Services registados", 'cyan');

        $nomes = [];

        foreach ($this->ficheirosPhp() as $ficheiro) {
            $codigo = file_get_contents($ficheiro);

            if (preg_match_all("/service\(\s*'(\w+)'/", $codigo, $m)) {
                foreach ($m[1] as $nome) {
                    $nomes[$nome][] = $ficheiro;
                }
            }
        }

        $erros = 0;

        foreach ($nomes as $nome => $ficheiros) {
            try {
                $resolvido = service($nome);
            } catch (Throwable) {
                $resolvido = null;
            }

            if ($resolvido === null) {
                CLI::write("   ✗ service('{$nome}') não resolve — "
                    . $this->relativo($ficheiros[0]), 'red');
                $erros++;
            }
        }

        $this->problemas += $erros;
        $erros === 0 && CLI::write('   ✓ ' . count($nomes) . ' service(s) resolvem.', 'green');
    }

    /**
     * Um '?>' num comentário DE LINHA (iniciado por // ou #) fecha mesmo
     * o bloco PHP e provoca ParseError.
     *
     * Num comentário de bloco (docblock) o '?>' é inofensivo — o PHP não
     * o interpreta. Por isso só se verificam os comentários de linha.
     */
    private function verificarComentarios(): void
    {
        CLI::write("\n6. '?>' em comentários de linha", 'cyan');
        $erros = 0;

        foreach ($this->ficheirosPhp() as $ficheiro) {
            // As views usam ? > legitimamente; só interessam as classes.
            if (str_contains($ficheiro, DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            foreach (file($ficheiro) as $n => $linha) {
                $t = ltrim($linha);

                if ((str_starts_with($t, '//') || str_starts_with($t, '#'))
                    && str_contains($t, '?' . '>')) {
                    CLI::write('   ✗ ' . $this->relativo($ficheiro) . ':' . ($n + 1), 'red');
                    $erros++;
                }
            }
        }

        $this->problemas += $erros;
        $erros === 0 && CLI::write('   ✓ Sem "?>" em comentários de linha.', 'green');
    }

    // ============================ Auxiliares ============================

    /** @return string[] */
    private function ficheirosPhp(): array
    {
        $iterador = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(APPPATH, \FilesystemIterator::SKIP_DOTS)
        );

        $ficheiros = [];

        foreach ($iterador as $f) {
            if ($f->getExtension() === 'php') {
                $ficheiros[] = $f->getPathname();
            }
        }

        return $ficheiros;
    }

    private function relativo(string $caminho): string
    {
        return str_replace(ROOTPATH, '', $caminho);
    }
}
