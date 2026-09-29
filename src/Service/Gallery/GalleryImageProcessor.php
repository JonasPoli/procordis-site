<?php

namespace App\Service\Gallery;

use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Psr\Log\LoggerInterface;

/**
 * Gera as versões redimensionadas das imagens da galeria (via Imagine/LiipImagine, driver GD).
 *
 * - corrige a orientação pelo EXIF (fotos de celular) e remove os metadados (inclusive GPS);
 * - nunca amplia: a imagem só é reduzida para caber no tamanho máximo de cada versão;
 * - salva em WebP quando o servidor suporta; senão, JPEG (ou PNG, se a original for PNG).
 */
class GalleryImageProcessor
{
    /** Lado maior máximo (px) de cada versão. */
    public const VARIANTS = [
        'thumb' => 400,
        'medium' => 1200,
        'large' => 2400,
    ];

    private const QUALITY = [
        'thumb' => 76,
        'medium' => 82,
        'large' => 86,
    ];

    /** Limite de segurança para não estourar a memória do PHP ao abrir a imagem com GD. */
    public const MAX_MEGAPIXELS = 50;

    private ?bool $webpSupported = null;

    public function __construct(
        private readonly ImagineInterface $imagine,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param list<string> $variants    nomes de self::VARIANTS a gerar
     * @param bool         $trimLetterbox remove faixas pretas (capas 4:3 do YouTube com vídeo 16:9)
     *
     * @return array<string, array{path: string, width: int, height: int}> caminhos relativos a $relativeDir
     */
    public function process(
        string $sourcePath,
        string $absoluteDir,
        string $relativeDir,
        string $baseName,
        array $variants,
        string $sourceMimeType = 'image/jpeg',
        bool $trimLetterbox = false,
    ): array {
        $info = @getimagesize($sourcePath);
        if (false === $info || $info[0] < 1 || $info[1] < 1) {
            throw new GalleryException('Não foi possível ler a imagem. O arquivo pode estar corrompido.');
        }

        if ($info[0] * $info[1] > self::MAX_MEGAPIXELS * 1_000_000) {
            throw new GalleryException(sprintf(
                'A imagem é grande demais (%d × %d pixels). O limite é de %d megapixels; reduza a foto e envie novamente.',
                $info[0],
                $info[1],
                self::MAX_MEGAPIXELS
            ));
        }

        $this->ensureMemory();

        try {
            $image = $this->imagine->open($sourcePath);
            $this->autorotate($image, $sourcePath);
            $image->strip();

            if ($trimLetterbox) {
                $image = $this->trimLetterbox($image);
            }
        } catch (GalleryException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Falha ao abrir imagem da galeria: '.$e->getMessage(), ['exception' => $e]);

            throw new GalleryException('Não foi possível processar a imagem. Verifique se o arquivo é um JPG, PNG ou WebP válido.');
        }

        [$format, $extension] = $this->outputFormat($sourceMimeType);
        $generated = [];

        try {
            foreach ($variants as $variant) {
                $max = self::VARIANTS[$variant] ?? throw new \InvalidArgumentException('Versão desconhecida: '.$variant);
                $size = $image->getSize();

                $resized = ($size->getWidth() > $max || $size->getHeight() > $max)
                    ? $image->thumbnail(new Box($max, $max), ImageInterface::THUMBNAIL_INSET)
                    : $image->copy();

                $filename = sprintf('%s-%s.%s', $baseName, $variant, $extension);
                $absolute = rtrim($absoluteDir, '/').'/'.$filename;

                $resized->save($absolute, [
                    'format' => $format,
                    'webp_quality' => self::QUALITY[$variant],
                    'jpeg_quality' => self::QUALITY[$variant],
                    'png_compression_level' => 8,
                ]);
                @chmod($absolute, 0664);

                $resizedSize = $resized->getSize();
                $generated[$variant] = [
                    'path' => trim($relativeDir, '/').'/'.$filename,
                    'width' => $resizedSize->getWidth(),
                    'height' => $resizedSize->getHeight(),
                ];
            }
        } catch (\Throwable $e) {
            foreach ($generated as $file) {
                @unlink(rtrim($absoluteDir, '/').'/'.basename($file['path']));
            }

            $this->logger->error('Falha ao gerar versões da imagem da galeria: '.$e->getMessage(), ['exception' => $e]);

            throw new GalleryException('Não foi possível gerar as versões da imagem no servidor. Tente novamente.');
        }

        return $generated;
    }

    public function isWebpSupported(): bool
    {
        if (null === $this->webpSupported) {
            $this->webpSupported = !$this->imagine instanceof GdImagine
                || (\function_exists('imagewebp') && (bool) (gd_info()['WebP Support'] ?? false));
        }

        return $this->webpSupported;
    }

    /**
     * @return array{0: string, 1: string} [formato, extensão]
     */
    private function outputFormat(string $sourceMimeType): array
    {
        if ($this->isWebpSupported()) {
            return ['webp', 'webp'];
        }

        return 'image/png' === $sourceMimeType ? ['png', 'png'] : ['jpeg', 'jpg'];
    }

    /**
     * Aplica a orientação EXIF (1–8) e deixa a imagem "em pé" como o fotógrafo viu.
     */
    private function autorotate(ImageInterface $image, string $sourcePath): void
    {
        $orientation = null;

        try {
            $metadata = $image->metadata();
            $orientation = isset($metadata['ifd0.Orientation']) ? (int) $metadata['ifd0.Orientation'] : null;
        } catch (\Throwable) {
        }

        if (null === $orientation && \function_exists('exif_read_data')) {
            $exif = @exif_read_data($sourcePath);
            $orientation = isset($exif['Orientation']) ? (int) $exif['Orientation'] : null;
        }

        $black = $image->palette()->color('000000');

        match ($orientation) {
            2 => $image->flipHorizontally(),
            3 => $image->rotate(180, $black),
            4 => $image->flipVertically(),
            5 => $image->flipHorizontally()->rotate(-90, $black),
            6 => $image->rotate(90, $black),
            7 => $image->flipHorizontally()->rotate(90, $black),
            8 => $image->rotate(-90, $black),
            default => null,
        };
    }

    /**
     * Capas "sddefault"/"hqdefault" do YouTube são 4:3 com faixas pretas em cima e embaixo
     * quando o vídeo é 16:9. Se as faixas forem detectadas, recorta o centro em 16:9.
     */
    private function trimLetterbox(ImageInterface $image): ImageInterface
    {
        $size = $image->getSize();
        $width = $size->getWidth();
        $height = $size->getHeight();
        $targetHeight = (int) round($width * 9 / 16);

        if ($targetHeight >= $height - 4) {
            return $image;
        }

        $band = (int) floor(($height - $targetHeight) / 2);
        $rowsToCheck = [(int) max(1, $band * 0.3), (int) max(1, $band * 0.7), $height - (int) max(1, $band * 0.3) - 1, $height - (int) max(1, $band * 0.7) - 1];

        foreach ($rowsToCheck as $y) {
            for ($i = 1; $i <= 12; ++$i) {
                $color = $image->getColorAt(new Point((int) ($width * $i / 13), $y));
                $luminance = 0.2126 * $color->getValue('red') + 0.7152 * $color->getValue('green') + 0.0722 * $color->getValue('blue');

                if ($luminance > 24) {
                    return $image; // não é uma faixa preta: mantém a imagem inteira
                }
            }
        }

        return $image->crop(new Point(0, $band), new Box($width, $targetHeight));
    }

    private function ensureMemory(): void
    {
        $limit = ini_get('memory_limit');
        if (false === $limit || '-1' === $limit) {
            return;
        }

        $bytes = $this->toBytes($limit);
        if ($bytes > 0 && $bytes < 512 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }
    }

    private function toBytes(string $value): int
    {
        $value = trim($value);
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
