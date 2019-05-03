<?php

namespace Pumukit\LmsBundle\EventListener;

use Pumukit\LmsBundle\Services\LmsService;
use Pumukit\PersonalRecorderBundle\Event\CreateEvent;

class PersonalRecorderEventListener
{
    /**
     * @var LmsService
     */
    private $lmsService;

    /**
     * PersonalRecorderEventListener constructor.
     *
     * @param LmsService $lmsService
     */
    public function __construct(LmsService $lmsService)
    {
        $this->lmsService = $lmsService;
    }

    /**
     * @param CreateEvent $event
     *
     * @throws \Exception
     */
    public function postCreateMultimediaObject(CreateEvent $event)
    {
        $user = $event->getUser();
        $multimediaObject = $event->getMultimediaObject();
        if (!$user->hasRole('ROLE_TAG_DEFAULT_PUCHWEBTV')) {
            $this->lmsService->addPublicationChannelToMultimediaObject($multimediaObject);
        }
    }
}
