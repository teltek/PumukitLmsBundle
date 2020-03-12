<?php

namespace Pumukit\LmsBundle\EventListener;

use Pumukit\LmsBundle\Services\LmsService;
use Pumukit\PersonalRecorderBundle\Event\CreateEvent;

class PersonalRecorderEventListener
{
    /** @var LmsService */
    private $lmsService;

    public function __construct(LmsService $lmsService)
    {
        $this->lmsService = $lmsService;
    }

    public function postCreateMultimediaObject(CreateEvent $event): void
    {
        $user = $event->getUser();
        $multimediaObject = $event->getMultimediaObject();
        if (!$user->hasRole('ROLE_TAG_DEFAULT_PUCHWEBTV')) {
            $this->lmsService->addPublicationChannelToMultimediaObject($multimediaObject);
        }
    }
}
