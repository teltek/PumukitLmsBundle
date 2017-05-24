<?php

namespace Pumukit\OpenEdxBundle\EventListener;

use Pumukit\WizardBundle\Event\FormEvent;
use Pumukit\OpenEdxBundle\Services\OpenEdxService;

class WizardEventListener
{
    private $openEdxService;

    public function __construct(OpenEdxService $openEdxService)
    {
        $this->openEdxService = $openEdxService;
    }

    public function postCreateMultimediaObject(FormEvent $event)
    {
        $user = $event->getUser();
        $form = $event->getForm();
        $externalData = array();
        if (isset($form['externalData'])) {
            $externalData = $form['externalData'];
        }
        $multimediaObject = $event->getMultimediaObject();
        if (isset($form['simple']) && $form['simple']) {
            $this->openEdxService->addPublicationChannelToMultimediaObject($user, $multimediaObject, $externalData);
        }
    }
}
