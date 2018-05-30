<?php

namespace Pumukit\LmsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\TagService;

class OpenEdxService
{
    private $dm;
    private $tagService;

    /**
     * Constructor.
     *
     * @param DocumentManager $dm
     * @param TagService      $tagService
     */
    public function __construct(DocumentManager $dm, TagService $tagService)
    {
        $this->dm = $dm;
        $this->tagService = $tagService;
    }

    public function addPublicationChannelToMultimediaObject(User $user, MultimediaObject $multimediaObject, $externalData)
    {
        if ($multimediaObject->getProperty('openedx')) {
            $this->tagService->addTagByCodToMultimediaObject($multimediaObject, 'PUCHLMS');
        }
    }
}
