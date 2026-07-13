<?php

declare(strict_types=1);

namespace App\Services\Comum;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Exportação de listagens para CSV (abre no Excel).
 *
 * Nota: escreve BOM UTF-8 para que o Excel apresente corretamente
 * os acentos portugueses, e usa ';' como separador (padrão pt/AO).
 */
final class ExportacaoService
{
    /**
     * @param array $colunas ['campo' => 'Rótulo']
     * @param array $linhas  array de objetos ou arrays
     */
    public function csv(string $nomeFicheiro, array $colunas, array $linhas): ResponseInterface
    {
        $saida = fopen('php://temp', 'r+');

        // BOM UTF-8 (Excel)
        fwrite($saida, "\xEF\xBB\xBF");

        fputcsv($saida, array_values($colunas), ';');

        foreach ($linhas as $linha) {
            $l = (array) $linha;
            $valores = [];

            foreach (array_keys($colunas) as $campo) {
                $valores[] = $l[$campo] ?? '';
            }

            fputcsv($saida, $valores, ';');
        }

        rewind($saida);
        $conteudo = stream_get_contents($saida);
        fclose($saida);

        return service('response')
            ->setContentType('text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition',
                'attachment; filename="' . $nomeFicheiro . '-' . date('Y-m-d') . '.csv"')
            ->setBody($conteudo);
    }
}
