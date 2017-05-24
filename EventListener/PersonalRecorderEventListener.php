<?php

namespace Pumukit\OpenEdxBundle\EventListener;

use Pumukit\PersonalRecorderBundle\Event\CreateEvent;
use Pumukit\OpenEdxBundle\Services\OpenEdxService;

class PersonalRecorderEventListener
{
    private $openEdxService;

    public function __construct(OpenEdxService $openEdxService)
    {
        $this->openEdxService = $openEdxService;
    }

    public function postCreateMultimediaObject(CreateEvent $event)
    {
        $user = $event->getUser();
        $externalData = $event->getExternalData();
        $multimediaObject = $event->getMultimediaObject();
        if (!$user->hasRole('ROLE_TAG_DEFAULT_PUCHWEBTV')) {
            $this->openEdxService->addPublicationChannelToMultimediaObject($user, $multimediaObject, $externalData);
        }
    }
}
