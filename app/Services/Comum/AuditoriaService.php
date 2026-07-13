<?php

declare(strict_types=1);

namespace App\Services\Comum;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Ponto único de escrita na tabela auditoria_logs.
 *
 * Campos sensíveis são redigidos antes de gravar (nunca guardamos
 * senhas, tokens ou segredos em claro no log).
 */
final class AuditoriaService
{
    /** Campos cujo valor é substituído por [REDIGIDO] no log. */
    private const CAMPOS_SENSIVEIS = ['password', 'password_hash', 'secret', 'secret2', 'token', 'senha'];

    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function registar(
        string $acao,
        string $entidade,
        int|string|null $entidadeId = null,
        ?array $dadosAntes = null,
        ?array $dadosDepois = null,
        ?string $descricao = null,
    ): void {
        $request = service('request');

        $this->db->table('auditoria_logs')->insert([
            'user_id'      => auth()->loggedIn() ? auth()->id() : null,
            'acao'         => $acao,
            'entidade'     => $entidade,
            'entidade_id'  => $entidadeId,
            'descricao'    => $descricao,
            'dados_antes'  => $dadosAntes === null ? null : json_encode($this->redigir($dadosAntes), JSON_UNESCAPED_UNICODE),
            'dados_depois' => $dadosDepois === null ? null : json_encode($this->redigir($dadosDepois), JSON_UNESCAPED_UNICODE),
            'ip_address'   => $request->getIPAddress(),
            'user_agent'   => substr((string) $request->getUserAgent(), 0, 255),
            'created_at'   => utc_agora(), // helper de data (Fase 3): DATETIME em UTC
        ]);
    }

    private function redigir(array $dados): array
    {
        foreach (self::CAMPOS_SENSIVEIS as $campo) {
            if (array_key_exists($campo, $dados)) {
                $dados[$campo] = '[REDIGIDO]';
            }
        }

        return $dados;
    }
}
