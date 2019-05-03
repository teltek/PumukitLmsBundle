<?php

namespace Pumukit\LmsBundle\EventListener;

use Pumukit\LmsBundle\Services\LmsService;
use Pumukit\WizardBundle\Event\FormEvent;

class WizardEventListener
{
    /**
     * @var LmsService
     */
    private $lmsService;

    /**
     * WizardEventListener constructor.
     *
     * @param LmsService $lmsService
     */
    public function __construct(LmsService $lmsService)
    {
        $this->lmsService = $lmsService;
    }

    /**
     * @param FormEvent $event
     *
     * @throws \Exception
     */
    public function postCreateMultimediaObject(FormEvent $event)
    {
        $form = $event->getForm();
        $multimediaObject = $event->getMultimediaObject();
        if (isset($form['simple']) && $form['simple']) {
            $this->lmsService->addPublicationChannelToMultimediaObject($multimediaObject);
        }
    }
}
