<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\EventListener;

use Pumukit\LmsBundle\Services\LmsService;

class PersonalRecorderEventListener
{
    private $lmsService;

    public function __construct(LmsService $lmsService)
    {
        $this->lmsService = $lmsService;
    }

    public function postCreateMultimediaObject($event): void
    {
        if (class_exists('Pumukit\PersonalRecorderBundle\Event\CreateEvent')) {
            $user = $event->getUser();
            $multimediaObject = $event->getMultimediaObject();
            if (!$user->hasRole('ROLE_TAG_DEFAULT_PUCHWEBTV')) {
                $this->lmsService->addPublicationChannelToMultimediaObject($multimediaObject);
            }
        }
    }
}
