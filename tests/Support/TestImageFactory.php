<?php

namespace App\Tests\Support;

/**
 * Gera imagens reais (GD) para os testes, inclusive JPEG com orientação EXIF.
 */
final class TestImageFactory
{
    /**
     * @param array{0: int, 1: int, 2: int} $rgb
     */
    public static function jpeg(int $width, int $height, array $rgb = [40, 120, 200], ?int $exifOrientation = null): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, ...$rgb));
        // Marca no canto superior esquerdo, útil para conferir rotação visualmente.
        imagefilledrectangle($image, 0, 0, (int) ($width / 4), (int) ($height / 4), imagecolorallocate($image, 250, 250, 250));

        ob_start();
        imagejpeg($image, null, 90);
        $jpeg = (string) ob_get_clean();

        return null === $exifOrientation ? $jpeg : self::withExifOrientation($jpeg, $exifOrientation);
    }

    /**
     * Capa 4:3 com faixas pretas em cima e embaixo (como sddefault/hqdefault do YouTube para vídeo 16:9).
     */
    public static function letterboxedJpeg(int $width = 640, int $height = 480): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 0, 0, 0));
        $band = (int) (($height - $width * 9 / 16) / 2);
        imagefilledrectangle($image, 0, $band, $width - 1, $height - $band - 1, imagecolorallocate($image, 200, 60, 60));

        ob_start();
        imagejpeg($image, null, 92);
        $jpeg = (string) ob_get_clean();

        return $jpeg;
    }

    public static function png(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 10, 160, 90));

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();

        return $png;
    }

    public static function gif(int $width, int $height): string
    {
        $image = imagecreate($width, $height);
        imagecolorallocate($image, 255, 0, 0);

        ob_start();
        imagegif($image);
        $gif = (string) ob_get_clean();

        return $gif;
    }

    /**
     * Insere um segmento APP1/EXIF mínimo (IFD0 com a tag Orientation) logo após o SOI do JPEG.
     */
    public static function withExifOrientation(string $jpeg, int $orientation): string
    {
        $tiff = 'MM'."\x00\x2A".pack('N', 8)
            .pack('n', 1)
            .pack('n', 0x0112).pack('n', 3).pack('N', 1).pack('n', $orientation)."\x00\x00"
            .pack('N', 0);
        $exif = "Exif\x00\x00".$tiff;
        $app1 = "\xFF\xE1".pack('n', \strlen($exif) + 2).$exif;

        return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
    }

    /**
     * Grava o conteúdo num arquivo temporário e devolve o caminho.
     */
    public static function toTempFile(string $content, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'gallery_test_').'.'.$extension;
        file_put_contents($path, $content);

        return $path;
    }
}
