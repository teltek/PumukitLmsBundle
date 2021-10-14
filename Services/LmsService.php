<?php

namespace Pumukit\LmsBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\TagService;

class LmsService
{
    public const LMS_TAG_CODE = 'PUCHLMS';
    private $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    public function addPublicationChannelToMultimediaObject(MultimediaObject $multimediaObject): void
    {
        if ($multimediaObject->getProperty('openedx') || $multimediaObject->getProperty('lms')) {
            $this->tagService->addTagByCodToMultimediaObject($multimediaObject, self::LMS_TAG_CODE);
        }
    }
}
