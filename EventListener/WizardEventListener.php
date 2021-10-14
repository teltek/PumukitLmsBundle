<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\EventListener;

use Pumukit\LmsBundle\Services\LmsService;
use Pumukit\WizardBundle\Event\FormEvent;

class WizardEventListener
{
    private $lmsService;

    public function __construct(LmsService $lmsService)
    {
        $this->lmsService = $lmsService;
    }

    public function postCreateMultimediaObject(FormEvent $event): void
    {
        $form = $event->getForm();
        $multimediaObject = $event->getMultimediaObject();
        if (isset($form['simple']) && $form['simple']) {
            $this->lmsService->addPublicationChannelToMultimediaObject($multimediaObject);
        }
    }
}
