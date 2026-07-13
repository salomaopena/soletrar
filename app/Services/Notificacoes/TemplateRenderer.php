<?php

declare(strict_types=1);

namespace App\Services\Notificacoes;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Renderização de templates de notificação ({{placeholders}}).
 *
 * Convenção de códigos: "{evento}_{canal}", ex.: inscricao_validada_sms.
 * Placeholders não fornecidos ficam vazios e geram warning no log —
 * nunca aparecem "{{candidato_nome}}" cru num SMS a um encarregado.
 */
final class TemplateRenderer
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /** Templates ATIVOS de um evento, indexados por canal. */
    public function templatesDoEvento(string $evento): array
    {
        $linhas = $this->db->table('notificacoes_templates')
            ->whereIn('codigo', [
                "{$evento}_sistema", "{$evento}_email", "{$evento}_sms",
            ])
            ->where('ativo', 1)
            ->get()->getResultArray();

        return array_column($linhas, null, 'canal');
    }

    /** Substitui {{chave}} pelos dados; devolve ['assunto' => ?, 'corpo' => string]. */
    public function renderizar(array $template, array $dados): array
    {
        return [
            'assunto' => $template['assunto'] !== null
                ? $this->substituir($template['assunto'], $dados)
                : null,
            'corpo'   => $this->substituir($template['corpo'], $dados),
        ];
    }

    private function substituir(string $texto, array $dados): string
    {
        $resultado = preg_replace_callback(
            '/\{\{\s*([a-z0-9_]+)\s*\}\}/i',
            static function (array $m) use ($dados, $texto): string {
                if (! array_key_exists($m[1], $dados)) {
                    log_message('warning', 'Template: placeholder {{ {ph} }} sem valor.', ['ph' => $m[1]]);

                    return '';
                }

                return (string) $dados[$m[1]];
            },
            $texto
        );

        return trim($resultado);
    }
}
