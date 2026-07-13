<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Conteúdo inicial para o sistema NÃO abrir vazio:
 *   - menus do cabeçalho e rodapé (tabelas `menus` + `menus_itens`);
 *   - páginas institucionais base;
 *   - categorias de notícias;
 *   - uma edição de exemplo com inscrições abertas + categorias.
 *
 * Idempotente: pode ser reexecutado sem duplicar.
 */
class ConteudoInicialSeeder extends Seeder
{
    public function run(): void
    {
        $this->paginas();
        $this->menus();
        $this->edicaoExemplo();
    }

    // ------------------------------------------------------------------
    private function paginas(): void
    {
        helper('texto');

        $paginas = [
            ['O concurso', 'Informação institucional sobre o Concurso Nacional de Soletração.'],
            ['Regulamento', 'Regras de participação, fases e critérios de avaliação.'],
            ['Contactos',   'Contactos da coordenação nacional.'],
        ];

        foreach ($paginas as [$titulo, $conteudo]) {
            $slug = slug_pt($titulo);

            if ($this->db->table('paginas')->where('slug', $slug)->countAllResults() > 0) {
                continue;
            }

            $this->db->table('paginas')->insert([
                'titulo'         => $titulo,
                'slug'           => $slug,
                'conteudo'       => '<p>' . $conteudo . '</p>',
                'status'         => 'publicada',
                'mostra_no_menu' => 1,
                'ordem'          => 0,
                'autor_id'       => 1,
                'created_at'     => utc_agora(),
                'updated_at'     => utc_agora(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    private function menus(): void
    {
        // 1. Os dois menus base
        foreach ([['Menu principal', 'header'], ['Menu do rodapé', 'footer']] as [$nome, $loc]) {
            if ($this->db->table('menus')->where('localizacao', $loc)->countAllResults() === 0) {
                $this->db->table('menus')->insert(['nome' => $nome, 'localizacao' => $loc, 'ativo' => 1]);
            }
        }

        $header = $this->db->table('menus')->where('localizacao', 'header')->get()->getRow();
        $footer = $this->db->table('menus')->where('localizacao', 'footer')->get()->getRow();

        // 2. Itens (coluna real: `label`, não `titulo`)
        $itens = [
            // menu, label, tipo, url, ordem
            [$header->id, 'Início',      'custom', '/',           1],
            [$header->id, 'Notícias',    'custom', '/noticias',   2],
            [$header->id, 'Resultados',  'custom', '/resultados', 3],
            [$footer->id, 'O concurso',  'custom', '/pagina/o-concurso',  1],
            [$footer->id, 'Regulamento', 'custom', '/pagina/regulamento', 2],
            [$footer->id, 'Contactos',   'custom', '/pagina/contactos',   3],
        ];

        foreach ($itens as [$menuId, $label, $tipo, $url, $ordem]) {
            $existe = $this->db->table('menus_itens')
                ->where(['menu_id' => $menuId, 'label' => $label])
                ->countAllResults() > 0;

            if ($existe) {
                continue;
            }

            $this->db->table('menus_itens')->insert([
                'menu_id' => $menuId,
                'label'   => $label,
                'tipo'    => $tipo,
                'url'     => $url,
                'target'  => '_self',
                'ordem'   => $ordem,
            ]);
        }
    }

    // ------------------------------------------------------------------
    private function edicaoExemplo(): void
    {
        if ($this->db->table('edicoes_concurso')->countAllResults() > 0) {
            return;
        }

        helper('texto');
        $ano = (int) date('Y');

        $this->db->table('edicoes_concurso')->insert([
            'ano'                          => $ano,
            'nome'                         => "Concurso Nacional de Soletração {$ano}",
            'slug'                         => slug_pt("Concurso Nacional de Soletracao {$ano}"),
            'tema'                         => 'A língua portuguesa em palco',
            'status'                       => 'inscricoes_abertas',
            'data_abertura_inscricoes'     => date('Y-m-d H:i:s', strtotime('-7 days')),
            'data_encerramento_inscricoes' => date('Y-m-d H:i:s', strtotime('+60 days')),
            'data_inicio'                  => date('Y-m-d', strtotime('+90 days')),
            'classe_minima'                => 1,
            'classe_maxima'                => 8,
            'created_at'                   => utc_agora(),
            'updated_at'                   => utc_agora(),
        ]);

        $edicaoId = (int) $this->db->insertID();

        // Categorias por classe
        $this->db->table('categorias_competicao')->insertBatch([
            ['edicao_id' => $edicaoId, 'nome' => 'Categoria A (1.ª–4.ª classe)',
             'classe_minima' => 1, 'classe_maxima' => 4, 'ordem' => 1, 'created_at' => utc_agora()],
            ['edicao_id' => $edicaoId, 'nome' => 'Categoria B (5.ª–8.ª classe)',
             'classe_minima' => 5, 'classe_maxima' => 8, 'ordem' => 2, 'created_at' => utc_agora()],
        ]);

        // Fases (valores conforme os ENUM do esquema:
        // tipo_fase: escolar|municipal|provincial|semifinal_nacional|final_nacional
        // status:    agendada|em_curso|concluida|cancelada)
        $this->db->table('fases_concurso')->insertBatch([
            ['edicao_id' => $edicaoId, 'nome' => 'Fase Escolar',    'tipo_fase' => 'escolar',
             'ordem' => 1, 'vagas_proxima_fase' => 3, 'status' => 'agendada',
             'created_at' => utc_agora(), 'updated_at' => utc_agora()],
            ['edicao_id' => $edicaoId, 'nome' => 'Fase Provincial', 'tipo_fase' => 'provincial',
             'ordem' => 2, 'vagas_proxima_fase' => 2, 'status' => 'agendada',
             'created_at' => utc_agora(), 'updated_at' => utc_agora()],
            ['edicao_id' => $edicaoId, 'nome' => 'Final Nacional',  'tipo_fase' => 'final_nacional',
             'ordem' => 3, 'vagas_proxima_fase' => 0, 'status' => 'agendada',
             'created_at' => utc_agora(), 'updated_at' => utc_agora()],
        ]);
    }
}
