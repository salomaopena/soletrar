<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * As 21 províncias de Angola (divisão administrativa de 2024).
 * Os códigos de 3 letras são usados no número de inscrição (ANO-COD-SEQ).
 */
class ProvinciasSeeder extends Seeder
{
    public function run(): void
    {
        $provincias = [
            ['Bengo', 'BGO'], ['Benguela', 'BGU'], ['Bié', 'BIE'], ['Cabinda', 'CAB'],
            ['Cuando', 'CDO'], ['Cubango', 'CBG'], ['Cuanza Norte', 'CNO'], ['Cuanza Sul', 'CSU'],
            ['Cunene', 'CNN'], ['Huambo', 'HUA'], ['Huíla', 'HUI'], ['Icolo e Bengo', 'ICB'],
            ['Luanda', 'LDA'], ['Lunda Norte', 'LNO'], ['Lunda Sul', 'LSU'], ['Malanje', 'MAL'],
            ['Moxico', 'MOX'], ['Moxico Leste', 'MXL'], ['Namibe', 'NAM'], ['Uíge', 'UIG'],
            ['Zaire', 'ZAI'],
        ];

        $agora = date('Y-m-d H:i:s');
        $linhas = array_map(static fn ($p) => [
            'nome' => $p[0], 'codigo' => $p[1], 'ativo' => 1,
            'created_at' => $agora, 'updated_at' => $agora,
        ], $provincias);

        // Idempotente: ignora se já existir (permite reexecutar o seeder).
        $this->db->table('provincias')->ignore(true)->insertBatch($linhas);
    }
}
