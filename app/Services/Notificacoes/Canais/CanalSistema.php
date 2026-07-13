<?php

declare(strict_types=1);

namespace App\Services\Notificacoes\Canais;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Notificações internas (o "sino"). Escrita direta e síncrona em
 * notificacoes — este canal não passa pela fila (não há provedor
 * externo que possa falhar), mas implementa a interface para
 * uniformidade.
 */
final class CanalSistema implements CanalInterface
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function enviar(array $item): bool
    {
        return $this->criar(
            (int) $item['user_id'],
            $item['dados_json']['tipo'] ?? 'geral',
            $item['assunto'] ?? '',
            $item['corpo'],
            $item['dados_json']['link'] ?? null,
        );
    }

    public function criar(int $userId, string $tipo, string $titulo, string $mensagem, ?string $link = null): bool
    {
        $this->db->table('notificacoes')->insert([
            'user_id'    => $userId,
            'tipo'       => $tipo,
            'titulo'     => $titulo,
            'mensagem'   => $mensagem,
            'link'       => $link,
            'created_at' => utc_agora(),
        ]);

        return true;
    }
}
