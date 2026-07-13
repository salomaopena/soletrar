<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * Entidade Notícia.
 *
 * Concentra TODA a lógica de apresentação e SEO, para que as views
 * nunca precisem de calcular fallbacks nem formatar estados.
 */
class Noticia extends Entity
{
    protected $casts = [
        'id'                   => 'integer',
        'destaque'             => 'boolean',
        'fixada'               => 'boolean',
        'permitir_comentarios' => 'boolean',
        'visualizacoes'        => 'integer',
        'data_publicacao'      => '?datetime',
        'data_agendada'        => '?datetime',
    ];

    /** Estados considerados visíveis no portal público. */
    public function estaPublicada(): bool
    {
        return $this->attributes['status'] === 'publicada'
            && $this->data_publicacao !== null
            && $this->data_publicacao->getTimestamp() <= time();
    }

    /** URL pública canónica (slug, nunca ID — decisão de SEO da Fase 4). */
    public function urlPublica(): string
    {
        return site_url('noticias/' . $this->attributes['slug']);
    }

    // ------------------------- SEO (fallbacks) -------------------------

    public function metaTitulo(): string
    {
        return $this->attributes['meta_titulo'] ?: $this->attributes['titulo'];
    }

    public function metaDescricao(): string
    {
        if ($this->attributes['meta_descricao']) {
            return $this->attributes['meta_descricao'];
        }

        // Fallback: resumo, ou primeiro excerto do conteúdo sem HTML.
        $base = $this->attributes['resumo']
            ?: strip_tags((string) $this->attributes['conteudo']);

        return mb_substr(trim(preg_replace('/\s+/', ' ', $base)), 0, 160);
    }

    /** Imagem Open Graph: og_imagem > imagem destacada > logotipo do site. */
    public function ogImagem(): string
    {
        if ($this->attributes['og_imagem']) {
            return base_url($this->attributes['og_imagem']);
        }
        if (! empty($this->attributes['imagem_destacada_url'])) {   // vem do join
            return base_url($this->attributes['imagem_destacada_url']);
        }

        return base_url('assets/img/og-padrao.png');
    }

    /** Tempo de leitura estimado (200 palavras/min), com mínimo de 1. */
    public function tempoLeituraMin(): int
    {
        if (! empty($this->attributes['tempo_leitura_min'])) {
            return (int) $this->attributes['tempo_leitura_min'];
        }

        $palavras = str_word_count(strip_tags((string) $this->attributes['conteudo']));

        return max(1, (int) ceil($palavras / 200));
    }

    /** Rótulo humano do estado, para badges no backoffice. */
    public function estadoRotulo(): string
    {
        return lang('Cms.estado_' . $this->attributes['status']);
    }
}
