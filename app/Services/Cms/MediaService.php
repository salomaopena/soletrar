<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Services\Comum\UploadService;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

/**
 * Biblioteca de media (estilo WordPress).
 *
 * Único ponto de entrada de ficheiros do CMS. Usa o UploadService da
 * Fase 4 (whitelist de MIME real, renomeação aleatória, re-encode) e
 * acrescenta: registo em media_biblioteca, miniatura para a grelha do
 * backoffice e verificação de uso antes de eliminar.
 */
final class MediaService
{
    private const LARGURA_MINIATURA = 360;

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly UploadService $uploads,
    ) {
    }

    /**
     * Envia um ficheiro e regista-o na biblioteca. Devolve o ID do media.
     *
     * @param array $meta ['titulo' => ..., 'texto_alt' => ..., 'legenda' => ...]
     */
    public function enviar(UploadedFile $ficheiro, array $meta, int $userId): int
    {
        $mime = $ficheiro->getClientMimeType();
        $tipo = $this->tipoPorMime($mime);

        // Imagens usam o perfil com re-encode; documentos/áudio o próprio.
        $perfil  = $tipo === 'imagem' ? 'imagem_media' : ($tipo === 'audio' ? 'audio_palavra' : 'documento_inscricao');
        $caminho = $this->uploads->guardar($ficheiro, $perfil);

        [$largura, $altura] = $tipo === 'imagem'
            ? (getimagesize(FCPATH . 'uploads/' . $caminho) ?: [null, null])
            : [null, null];

        // Miniatura para a grelha da biblioteca (não substitui o original).
        if ($tipo === 'imagem') {
            $this->gerarMiniatura($caminho);
        }

        $this->db->table('media_biblioteca')->insert([
            'user_id'       => $userId,
            'nome_arquivo'  => basename($caminho),
            'nome_original' => $ficheiro->getClientName(),
            'caminho'       => $caminho,
            'url'           => 'uploads/' . $caminho,
            'mime_type'     => $mime,
            'tipo'          => $tipo,
            'tamanho_bytes' => $ficheiro->getSize(),
            'largura'       => $largura,
            'altura'        => $altura,
            'titulo'        => $meta['titulo'] ?? pathinfo($ficheiro->getClientName(), PATHINFO_FILENAME),
            'texto_alt'     => $meta['texto_alt'] ?? null,   // exigido p/ acessibilidade
            'legenda'       => $meta['legenda'] ?? null,
            'created_at'    => utc_agora(),
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Elimina um item, recusando se estiver em uso como imagem destacada.
     * Uso dentro do corpo das notícias não bloqueia (comportamento
     * WordPress), mas é avisado na interface antes da confirmação.
     */
    public function eliminar(int $mediaId): void
    {
        $emUso = $this->db->table('noticias')
            ->where('imagem_destacada_id', $mediaId)
            ->countAllResults();

        if ($emUso > 0) {
            throw new RuntimeException(lang('Cms.mediaEmUso', [$emUso]));
        }

        $media = $this->db->table('media_biblioteca')->where('id', $mediaId)->get()->getRow()
            ?? throw new RuntimeException(lang('Cms.mediaNaoEncontrado'));

        @unlink(FCPATH . 'uploads/' . $media->caminho);
        @unlink($this->caminhoMiniatura($media->caminho));

        $this->db->table('media_biblioteca')->where('id', $mediaId)->delete();
    }

    // ------------------------------ Internos ------------------------------

    private function gerarMiniatura(string $caminho): void
    {
        service('image')
            ->withFile(FCPATH . 'uploads/' . $caminho)
            ->resize(self::LARGURA_MINIATURA, self::LARGURA_MINIATURA, true, 'width')
            ->save($this->caminhoMiniatura($caminho), 80);
    }

    private function caminhoMiniatura(string $caminho): string
    {
        $info = pathinfo($caminho);

        return FCPATH . 'uploads/' . $info['dirname'] . '/mini_' . $info['basename'];
    }

    private function tipoPorMime(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'imagem',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            $mime === 'application/pdf'      => 'documento',
            default                          => 'outro',
        };
    }
}
