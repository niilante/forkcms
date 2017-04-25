<?php

namespace Backend\Modules\MediaLibrary\Ajax;

use Backend\Core\Engine\Base\AjaxAction as BackendBaseAJAXAction;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use Backend\Core\Language\Language;
use Backend\Modules\MediaLibrary\Domain\MediaFolder\Command\CreateMediaFolder;
use Backend\Modules\MediaLibrary\Domain\MediaFolder\MediaFolder;
use Common\Exception\AjaxExitException;
use Common\Uri;

/**
 * This AJAX-action will add a new MediaFolder.
 */
class MediaFolderAdd extends BackendBaseAJAXAction
{
    /**
     * Execute the action
     */
    public function execute()
    {
        parent::execute();

        /** @var CreateMediaFolder $createMediaFolder */
        $createMediaFolder = $this->createMediaFolder();

        // Success message
        $this->output(
            self::OK,
            $createMediaFolder->getMediaFolderEntity(),
            vsprintf(
                Language::msg('AddedFolder'),
                [
                    $createMediaFolder->getMediaFolderEntity()->getId()
                ]
            )
        );
    }

    /**
     * @return CreateMediaFolder
     */
    private function createMediaFolder()
    {
        /** @var MediaFolder|null $parent */
        $parent = $this->getParent();

        /** @var string $name */
        $name = $this->getFolderName($parent);

        /** @var CreateMediaFolder $createMediaFolder */
        $createMediaFolder = new CreateMediaFolder(
            $name,
            BackendAuthentication::getUser()->getUserId(),
            $parent
        );

        // Handle the MediaFolder create
        $this->get('command_bus')->handle($createMediaFolder);

        return $createMediaFolder;
    }

    /**
     * @param MediaFolder|null $parent
     * @throws AjaxExitException
     * @return string
     */
    protected function getFolderName(MediaFolder $parent = null): string
    {
        // Define name
        $name = $this->get('request')->request->get('name');

        // We don't have a name
        if ($name === null) {
            throw new AjaxExitException(Language::err('NameIsRequired'));
        }

        // Folder name already exists
        if ($this->get('media_library.repository.folder')->existsByName($name, $parent)) {
            throw new AjaxExitException(Language::err('MediaFolderExists'));
        }

        return $name;
    }

    /**
     * @return MediaFolder|null
     * @throws AjaxExitException
     */
    protected function getParent()
    {
        // Get parameters
        $parentId = $this->get('request')->request->getInt('parent_id');

        if ($parentId === 0) {
            return null;
        }

        try {
            return $this->get('media_library.repository.folder')->findOneById($parentId);
        } catch (\Exception $e) {
            throw new AjaxExitException(Language::err('ParentNotExists'));
        }
    }
}