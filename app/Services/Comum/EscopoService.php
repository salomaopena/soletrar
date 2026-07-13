<?php

declare(strict_types=1);

namespace App\Services\Comum;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Resolve o escopo territorial de um utilizador a partir da tabela
 * coordenadores_atribuicao (atribuições ativas e dentro do período).
 *
 * Regras:
 *  - superadmin e coord_nacional → escopo nacional (sem filtros);
 *  - restantes → união de todas as suas atribuições ativas;
 *  - sem atribuição ativa → escopo vazio (as listagens não devolvem nada,
 *    o que é o comportamento seguro por omissão).
 */
final class EscopoService
{
    /** Cache por request: [userId => Escopo] */
    private array $cache = [];

    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function doUtilizador(int $userId): Escopo
    {
        if (isset($this->cache[$userId])) {
            return $this->cache[$userId];
        }

        $utilizador = auth()->getProvider()->findById($userId);

        // Grupos com visão nacional não precisam de atribuições.
        if ($utilizador !== null && $utilizador->inGroup('superadmin', 'coord_nacional')) {
            return $this->cache[$userId] = Escopo::nacional();
        }

        $linhas = $this->db->table('coordenadores_atribuicao')
            ->select('nivel, provincia_id, municipio_id, escola_id')
            ->where('user_id', $userId)
            ->where('ativo', 1)
            ->groupStart()
                ->where('data_fim IS NULL')
                ->orWhere('data_fim >=', date('Y-m-d'))
            ->groupEnd()
            ->get()
            ->getResultArray();

        $provincias = $municipios = $escolas = [];
        $nivelMaisAbrangente = 'escolar';
        $pesos = ['escolar' => 1, 'municipal' => 2, 'provincial' => 3, 'nacional' => 4];

        foreach ($linhas as $linha) {
            if ($linha['nivel'] === 'nacional') {
                return $this->cache[$userId] = Escopo::nacional();
            }
            if ($pesos[$linha['nivel']] > $pesos[$nivelMaisAbrangente]) {
                $nivelMaisAbrangente = $linha['nivel'];
            }
            if ($linha['provincia_id']) { $provincias[] = (int) $linha['provincia_id']; }
            if ($linha['municipio_id']) { $municipios[] = (int) $linha['municipio_id']; }
            if ($linha['escola_id'])    { $escolas[]    = (int) $linha['escola_id']; }
        }

        return $this->cache[$userId] = new Escopo(
            nivel: $nivelMaisAbrangente,
            provincias: array_values(array_unique($provincias)),
            municipios: array_values(array_unique($municipios)),
            escolas: array_values(array_unique($escolas)),
        );
    }
}
