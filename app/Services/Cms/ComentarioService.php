<?php

declare(strict_types=1);

namespace App\Services\Cms;

use CodeIgniter\Database\ConnectionInterface;
use RuntimeException;

/**
 * Comentários com moderação (estilo WordPress).
 *
 * Defesas em camadas (além do throttle:5,5 na rota — Fase 4):
 *  1. honeypot: campo invisível "website_confirmar" — se vier preenchido,
 *     é um bot: descarta-se silenciosamente (o bot recebe "sucesso");
 *  2. verificação de que a notícia aceita comentários e está publicada;
 *  3. estado inicial conforme configuração 'comentarios_moderar';
 *  4. limite de links no corpo (spam clássico) força moderação.
 */
final class ComentarioService
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /**
     * Regista um comentário público. Devolve o estado atribuído
     * ('aprovado' | 'pendente') ou null se descartado por honeypot.
     */
    public function criar(array $dados): ?string
    {
        // 1. Honeypot: descartar em silêncio.
        if (! empty($dados['website_confirmar'])) {
            return null;
        }

        $noticia = $this->db->table('noticias')
            ->select('id, permitir_comentarios, status')
            ->where('id', (int) $dados['noticia_id'])
            ->get()->getRow();

        if ($noticia === null || ! $noticia->permitir_comentarios || $noticia->status !== 'publicada') {
            throw new RuntimeException(lang('Cms.comentariosFechados'));
        }

        // 2. Estado inicial: moderação global OU corpo suspeito (2+ links).
        $moderar = service('configuracao')->obter('comentarios_moderar', true)
            || substr_count(strtolower($dados['conteudo']), 'http') >= 2;

        $request = service('request');

        $this->db->table('noticias_comentarios')->insert([
            'noticia_id' => $noticia->id,
            'parent_id'  => $dados['parent_id'] ?? null,
            'user_id'    => auth()->loggedIn() ? auth()->id() : null,
            'nome_autor' => auth()->loggedIn() ? null : ($dados['nome_autor'] ?? null),
            'email_autor'=> auth()->loggedIn() ? null : ($dados['email_autor'] ?? null),
            'ip_autor'   => $request->getIPAddress(),
            'user_agent' => substr((string) $request->getUserAgent(), 0, 255),
            'conteudo'   => strip_tags($dados['conteudo']),   // comentários são texto puro
            'status'     => $moderar ? 'pendente' : 'aprovado',
            'created_at' => utc_agora(),
        ]);

        if ($moderar) {
            service('notificador')->notificarGrupo('editor_noticias', 'cms_comentario_pendente', [
                'noticia_id' => $noticia->id,
            ]);
        }

        return $moderar ? 'pendente' : 'aprovado';
    }

    /** Ações de moderação: 'aprovado' | 'spam' | 'lixeira'. */
    public function moderar(int $comentarioId, string $novoEstado): void
    {
        if (! in_array($novoEstado, ['aprovado', 'spam', 'lixeira'], true)) {
            throw new RuntimeException('Estado de moderação inválido.');
        }

        $this->db->table('noticias_comentarios')
            ->where('id', $comentarioId)
            ->update(['status' => $novoEstado, 'updated_at' => utc_agora()]);
    }

    /** Árvore de comentários aprovados de uma notícia (threaded). */
    public function aprovadosDe(int $noticiaId): array
    {
        $itens = $this->db->table('noticias_comentarios')
            ->where('noticia_id', $noticiaId)
            ->where('status', 'aprovado')
            ->orderBy('created_at')
            ->get()->getResultArray();

        $porId = array_column($itens, null, 'id');
        $raiz  = [];

        foreach ($porId as &$item) {
            $item['respostas'] ??= [];
            if ($item['parent_id'] !== null && isset($porId[$item['parent_id']])) {
                $porId[$item['parent_id']]['respostas'][] = &$item;
            } else {
                $raiz[] = &$item;
            }
        }

        return $raiz;
    }
}
