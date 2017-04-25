<?php

namespace Backend\Modules\MediaLibrary\Domain\MediaFolder;

use Backend\Modules\MediaLibrary\Domain\MediaGroup\MediaGroup;
use Backend\Modules\MediaLibrary\Domain\MediaItem\MediaItem;
use Doctrine\ORM\EntityRepository;
use Backend\Modules\MediaLibrary\Domain\MediaFolder\Exception\MediaFolderNotFound;

final class MediaFolderRepository extends EntityRepository
{
    /**
     * @param MediaFolder $mediaFolder
     *
     * We don't flush here, see http://disq.us/p/okjc6b
     */
    public function add(MediaFolder $mediaFolder)
    {
        $this->getEntityManager()->persist($mediaFolder);
    }

    /**
     * @param int $folderId
     * @param array &$counts
     */
    private function bumpFolderCount(int $folderId, array &$counts)
    {
        // Counts for folder doesn't exist
        if (!array_key_exists($folderId, $counts)) {
            // Init counts
            $counts[$folderId] = 1;

            return;
        }

        // Bump counts
        $counts[$folderId] += 1;
    }

    /**
     * Does a folder exists by name?
     *
     * @param string $name The requested folder name to check if exists.
     * @param MediaFolder|null $parent The parent MediaFolder where this folder should be in.
     * @return bool
     */
    public function existsByName(string $name, MediaFolder $parent = null): bool
    {
        /** @var MediaFolder $mediaFolder */
        $mediaFolder = $this->findOneBy([
            'name' => $name,
            'parent' => $parent,
        ]);

        return $mediaFolder instanceof MediaFolder;
    }

    /**
     * @return MediaFolder
     */
    public function findDefault(): MediaFolder
    {
        return $this->findBy([], ['name' => 'ASC'], 1)[0];
    }

    /**
     * @param int|null $id
     * @return MediaFolder
     * @throws \Exception
     */
    public function findOneById(int $id = null): MediaFolder
    {
        if ($id === null) {
            throw MediaFolderNotFound::forEmptyId();
        }

        $mediaFolder = parent::findOneById($id);

        if ($mediaFolder === null) {
            throw MediaFolderNotFound::forId($id);
        }

        return $mediaFolder;
    }

    /**
     * Get counts for media group
     *
     * @param MediaGroup $mediaGroup
     * @return array
     */
    public function getCountsForMediaGroup(MediaGroup $mediaGroup): array
    {
        // Init counts
        $counts = [];

        // Loop all connected items
        foreach ($mediaGroup->getConnectedItems() as $connectedItem) {
            $this->bumpFolderCount($connectedItem->getItem()->getFolder()->getId(), $counts);
        }

        return $counts;
    }

    /**
     * @param MediaFolder $mediaFolder
     *
     * We don't flush here, see http://disq.us/p/okjc6b
     */
    public function remove(MediaFolder $mediaFolder)
    {
        $this->getEntityManager()->remove($mediaFolder);
    }
}