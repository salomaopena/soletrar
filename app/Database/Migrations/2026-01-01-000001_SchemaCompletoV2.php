<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cria TODO o esquema a partir do SQL de referência v2.0.
 *
 * ESTRATÉGIA: o ficheiro app/Database/sql/schema_v2.sql é a fonte de
 * verdade (53 tabelas, views e triggers). Esta migration executa-o na
 * íntegra — evita divergência entre o SQL revisto e uma tradução manual
 * para o Forge, e preserva views/triggers que o Forge não suporta.
 *
 * NOTA: o SQL de referência v2.0 é AUTOSSUFICIENTE — cria também as
 * tabelas do Shield (users, auth_*), com a estrutura compatível e usando
 * IF NOT EXISTS. Por isso NÃO se corre `shield:setup`: basta
 *   php spark migrate
 * Se preferir gerir a autenticação via shield:setup, remova os blocos
 * users/auth_* do schema_v2.sql e corra shield:setup antes do migrate.
 */
class SchemaCompletoV2 extends Migration
{
    public function up(): void
    {
        $caminho = APPPATH . 'Database/sql/schema_v2.sql';

        if (! is_file($caminho)) {
            throw new \RuntimeException('SQL de referência não encontrado: ' . $caminho);
        }

        $sql = file_get_contents($caminho);

        // Executa o script. O SQL usa DELIMITER para triggers; separamos
        // por blocos respeitando os triggers (ver método abaixo).
        foreach ($this->dividirEmComandos($sql) as $comando) {
            $comando = trim($comando);
            if ($comando === '' || str_starts_with($comando, '--')) {
                continue;
            }
            $this->db->query($comando);
        }
    }

    public function down(): void
    {
        // Remoção completa: repor a base é recriar. Em produção nunca se
        // corre o down deste esquema base (usar migrations incrementais).
        $this->db->disableForeignKeyChecks();
        foreach ($this->db->listTables() as $tabela) {
            if (! str_starts_with($tabela, 'auth_') && $tabela !== 'users' && $tabela !== 'migrations') {
                $this->forge->dropTable($tabela, true);
            }
        }
        $this->db->enableForeignKeyChecks();
    }

    /**
     * Divide o script em comandos executáveis, tratando blocos
     * DELIMITER $$ ... $$ (triggers) como comandos únicos.
     */
    private function dividirEmComandos(string $sql): array
    {
        $comandos   = [];
        $delimitador = ';';
        $buffer     = '';

        foreach (preg_split('/\r?\n/', $sql) as $linha) {
            $trim = trim($linha);

            // Mudança de delimitador (triggers)
            if (stripos($trim, 'DELIMITER') === 0) {
                $delimitador = trim(substr($trim, 9)) ?: ';';
                continue;
            }

            $buffer .= $linha . "\n";

            if (str_ends_with(rtrim($linha), $delimitador)) {
                // Remove o delimitador final e fecha o comando.
                $comando = substr(rtrim($buffer), 0, -strlen($delimitador));
                $comandos[] = $comando;
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $comandos[] = $buffer;
        }

        return $comandos;
    }
}
