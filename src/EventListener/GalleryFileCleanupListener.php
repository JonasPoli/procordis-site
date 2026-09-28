<?php

namespace App\EventListener;

use App\Entity\NewsGalleryItem;
use App\Service\Gallery\GalleryStorage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Events;

/**
 * Apaga do disco os arquivos gerados de um item da galeria quando ele é excluído —
 * diretamente pelo painel ou em cascata, ao excluir a notícia.
 *
 * Os arquivos só são removidos depois do flush, para não apagar nada se a transação falhar.
 */
#[AsDoctrineListener(event: Events::postRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
final class GalleryFileCleanupListener
{
    /** @var list<string> */
    private array $pendingPaths = [];

    public function __construct(private readonly GalleryStorage $storage)
    {
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof NewsGalleryItem) {
            array_push($this->pendingPaths, ...$entity->getFilePaths());
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ([] === $this->pendingPaths) {
            return;
        }

        $paths = $this->pendingPaths;
        $this->pendingPaths = [];
        $this->storage->delete($paths);
    }
}
