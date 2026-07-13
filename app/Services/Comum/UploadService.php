<?php

declare(strict_types=1);

namespace App\Services\Comum;

use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

/**
 * Ponto único de upload de ficheiros da aplicação.
 *
 * Políticas aplicadas SEM exceção:
 *  1. Whitelist por perfil de upload (nunca blacklist).
 *  2. Validação do MIME REAL (finfo), não da extensão declarada.
 *  3. Renomeação criptograficamente aleatória (impede path traversal,
 *     colisões e adivinhação de nomes).
 *  4. Documentos privados ficam FORA de public/ e são servidos por
 *     controller com verificação de permissão + escopo.
 *  5. Imagens são re-processadas (re-encode), o que remove EXIF/GPS
 *     e neutraliza payloads embutidos.
 */
final class UploadService
{
    /** Perfis de upload: MIME permitidos e tamanho máximo (em KB). */
    private const PERFIS = [
        'imagem_media' => [
            'mimes'    => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'maxKb'    => 5120,
            'privado'  => false,
            'pasta'    => 'media',
            'reencode' => true,
        ],
        'documento_inscricao' => [
            'mimes'    => ['application/pdf', 'image/jpeg', 'image/png'],
            'maxKb'    => 8192,
            'privado'  => true,          // FORA de public/
            'pasta'    => 'inscricoes',
            'reencode' => false,
        ],
        'audio_palavra' => [
            'mimes'    => ['audio/mpeg', 'audio/ogg', 'audio/wav'],
            'maxKb'    => 4096,
            'privado'  => false,
            'pasta'    => 'palavras',
            'reencode' => false,
        ],
    ];

    /**
     * Guarda um ficheiro segundo um perfil e devolve o caminho relativo.
     *
     * @throws RuntimeException com mensagem traduzível em caso de rejeição
     */
    public function guardar(UploadedFile $ficheiro, string $perfil): string
    {
        $regras = self::PERFIS[$perfil]
            ?? throw new RuntimeException("Perfil de upload desconhecido: {$perfil}");

        if (! $ficheiro->isValid() || $ficheiro->hasMoved()) {
            throw new RuntimeException(lang('Geral.uploadInvalido'));
        }

        if ($ficheiro->getSizeByUnit('kb') > $regras['maxKb']) {
            throw new RuntimeException(lang('Geral.uploadDemasiadoGrande', [$regras['maxKb']]));
        }

        // MIME real via finfo — a extensão declarada pelo browser não conta.
        $mimeReal = (new File($ficheiro->getTempName()))->getMimeType();
        if (! in_array($mimeReal, $regras['mimes'], true)) {
            throw new RuntimeException(lang('Geral.uploadTipoNaoPermitido'));
        }

        $base = $regras['privado']
            ? WRITEPATH . 'uploads_privados/'
            : FCPATH . 'uploads/';

        // Subpasta por ano/mês para não degradar o filesystem.
        $pasta = $regras['pasta'] . '/' . date('Y/m');
        $nome  = bin2hex(random_bytes(16)) . '.' . $this->extensaoPorMime($mimeReal);

        if (! is_dir($base . $pasta) && ! mkdir($base . $pasta, 0755, true)) {
            throw new RuntimeException('Não foi possível criar a pasta de destino.');
        }

        $ficheiro->move($base . $pasta, $nome);
        $caminho = $pasta . '/' . $nome;

        // Re-encode de imagens: remove metadados e conteúdo malicioso.
        if ($regras['reencode']) {
            service('image')
                ->withFile($base . $caminho)
                ->reorient()          // corrige rotação EXIF antes de o descartar
                ->save($base . $caminho, 85);
        }

        return $caminho;
    }

    private function extensaoPorMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'image/gif'       => 'gif',
            'application/pdf' => 'pdf',
            'audio/mpeg'      => 'mp3',
            'audio/ogg'       => 'ogg',
            'audio/wav'       => 'wav',
            default           => 'bin',
        };
    }
}
