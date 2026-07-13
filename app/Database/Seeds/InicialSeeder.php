<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeder orquestrador: corre todos os seeders na ordem correta.
 * Uso: php spark db:seed InicialSeeder
 *
 * Muitos dados iniciais (categorias de palavras, de notícias,
 * configurações e templates) JÁ vêm no SQL de referência v2.0. Este
 * seeder acrescenta o que depende do Shield (grupos são geridos por
 * Config/AuthGroups) e cria o superadmin.
 */
class InicialSeeder extends Seeder
{
    public function run(): void
    {
        // As províncias já vêm no seed do SQL v2.0; reforço idempotente
        // caso se opte por gerir só por seeder.
        $this->call(ProvinciasSeeder::class);
        $this->call(SuperadminSeeder::class);
        $this->call(ConteudoInicialSeeder::class);
    }
}
