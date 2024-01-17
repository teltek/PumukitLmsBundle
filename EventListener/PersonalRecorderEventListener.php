<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\EventListener;

use Pumukit\LmsBundle\Services\LmsService;
use Pumukit\PersonalRecorderBundle\Event\CreateEvent;

class PersonalRecorderEventListener
{
    private $lmsService;

    public function __construct(LmsService $lmsService)
    {
        $this->lmsService = $lmsService;
    }

    public function postCreateMultimediaObject($event): void
    {
        if (class_exists(CreateEvent::class)) {
            $user = $event->getUser();
            $multimediaObject = $event->getMultimediaObject();
            if (!$user->hasRole('ROLE_TAG_DEFAULT_PUCHWEBTV')) {
                $this->lmsService->addPublicationChannelToMultimediaObject($multimediaObject);
            }
        }
    }
}
