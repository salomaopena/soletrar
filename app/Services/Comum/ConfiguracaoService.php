<?php

declare(strict_types=1);

namespace App\Services\Comum;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Leitura/escrita da tabela configuracoes, com cache e conversão de tipo
 * (string|integer|boolean|json — coluna `tipo`).
 */
final class ConfiguracaoService
{
    private const TTL_CACHE = 300;

    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function obter(string $chave, mixed $padrao = null): mixed
    {
        $todas = cache()->remember('configuracoes_todas', self::TTL_CACHE, function (): array {
            $linhas = $this->db->table('configuracoes')->get()->getResultArray();

            $mapa = [];
            foreach ($linhas as $l) {
                $mapa[$l['chave']] = match ($l['tipo']) {
                    'integer' => (int) $l['valor'],
                    'boolean' => in_array($l['valor'], ['1', 'true'], true),
                    'json'    => json_decode((string) $l['valor'], true),
                    default   => $l['valor'],
                };
            }

            return $mapa;
        });

        return $todas[$chave] ?? $padrao;
    }

    public function definir(string $chave, mixed $valor): void
    {
        $this->db->table('configuracoes')
            ->where('chave', $chave)
            ->update([
                'valor'      => is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : (string) $valor,
                'updated_at' => utc_agora(),
            ]);

        cache()->delete('configuracoes_todas');
    }
}
