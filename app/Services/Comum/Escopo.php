<?php

declare(strict_types=1);

namespace App\Services\Comum;

/**
 * Value object imutável que representa o escopo territorial do
 * utilizador autenticado. Resolvido uma vez por request pelo
 * EscopoService e injetado pelos filtros.
 */
final class Escopo
{
    /**
     * @param 'nacional'|'provincial'|'municipal'|'escolar' $nivel
     * @param int[] $provincias IDs de províncias abrangidas
     * @param int[] $municipios IDs de municípios abrangidos
     * @param int[] $escolas    IDs de escolas abrangidas
     */
    public function __construct(
        public readonly string $nivel,
        public readonly array $provincias = [],
        public readonly array $municipios = [],
        public readonly array $escolas = [],
    ) {
    }

    public static function nacional(): self
    {
        return new self('nacional');
    }

    public function eNacional(): bool
    {
        return $this->nivel === 'nacional';
    }

    public function abrangeProvincia(?int $provinciaId): bool
    {
        return $this->eNacional()
            || ($provinciaId !== null && in_array($provinciaId, $this->provincias, true));
    }

    public function abrangeEscola(?int $escolaId): bool
    {
        if ($this->eNacional()) {
            return true;
        }

        // Níveis provincial/municipal abrangem todas as escolas do seu território;
        // essa verificação é feita via abrangeProvincia() pelos consumidores.
        return $escolaId !== null && in_array($escolaId, $this->escolas, true);
    }
}
