<?php

namespace App\Service\Gallery;

use App\Entity\News;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Onde os arquivos da galeria ficam no disco e como viram URL.
 *
 * Padrão: public/images/news-gallery/{id da notícia}/{nome}-{versão}.{ext}
 * (mesma convenção dos demais uploads do projeto, que ficam em public/images/*).
 */
class GalleryStorage
{
    private readonly Filesystem $filesystem;

    public function __construct(
        #[Autowire('%app.news_gallery.dir%')]
        private readonly string $baseDir,
        #[Autowire('%app.news_gallery.url_prefix%')]
        private readonly string $urlPrefix,
        private readonly Packages $packages,
    ) {
        $this->filesystem = new Filesystem();
    }

    /**
     * Pasta relativa (à raiz da galeria) dos arquivos de uma notícia.
     */
    public function relativeDirFor(News $news): string
    {
        return (string) $news->getId();
    }

    /**
     * Garante que a pasta da notícia exista e devolve o caminho absoluto.
     */
    public function prepareDirFor(News $news): string
    {
        $dir = $this->absolutePath($this->relativeDirFor($news));

        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir, 0775);
        }

        if (!is_writable($dir)) {
            throw new GalleryException('A pasta de imagens da galeria não tem permissão de escrita no servidor. Avise o suporte técnico.');
        }

        return $dir;
    }

    public function absolutePath(string $relativePath): string
    {
        return rtrim($this->baseDir, '/').'/'.ltrim($relativePath, '/');
    }

    public function publicUrl(string $relativePath): string
    {
        return $this->packages->getUrl(trim($this->urlPrefix, '/').'/'.ltrim($relativePath, '/'));
    }

    /**
     * Apaga arquivos (caminhos relativos) e remove a pasta da notícia se ela ficar vazia.
     *
     * @param list<string> $relativePaths
     */
    public function delete(array $relativePaths): void
    {
        $dirs = [];

        foreach ($relativePaths as $relativePath) {
            if ('' === $relativePath || str_contains($relativePath, '..')) {
                continue;
            }

            $absolute = $this->absolutePath($relativePath);
            $this->filesystem->remove($absolute);
            $dirs[\dirname($absolute)] = true;
        }

        foreach (array_keys($dirs) as $dir) {
            if (is_dir($dir) && rtrim($dir, '/') !== rtrim($this->baseDir, '/') && [] === array_diff(scandir($dir) ?: [], ['.', '..'])) {
                @rmdir($dir);
            }
        }
    }
}
