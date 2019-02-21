<?php

namespace Pumukit\LmsBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class MultimediaObjectListener.
 */
class MultimediaObjectListener
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var array
     */
    private $allowedDomains;

    /**
     * @var string
     */
    private $defaultHost;

    /**
     * MultimediaObjectListener constructor.
     *
     * @param DocumentManager $documentManager
     * @param RequestStack    $requestStack
     * @param array           $allowedDomains
     * @param                 $defaultHost
     */
    public function __construct(DocumentManager $documentManager, RequestStack $requestStack, array $allowedDomains, $defaultHost)
    {
        $this->dm = $documentManager;
        $this->requestStack = $requestStack;
        $this->allowedDomains = $allowedDomains;
        $this->defaultHost = $defaultHost;
    }

    /**
     * @param MultimediaObjectEvent $multimediaObjectEvent
     */
    public function onMultimediaObjectCreate(MultimediaObjectEvent $multimediaObjectEvent)
    {
        $referer = $this->getRefererFromMasterRequest();

        $multimediaObject = $multimediaObjectEvent->getMultimediaObject();

        $refererUrl = $this->getOrigin($referer);

        $this->saveOriginMultimediaObject($multimediaObject, $refererUrl);
    }

    /**
     * @return array|string
     */
    private function getRefererFromMasterRequest()
    {
        $masterRequest = $this->requestStack->getMasterRequest();
        $referer = $masterRequest->headers->get('referer');

        return $referer;
    }

    /**
     * @param $referer
     *
     * @return mixed
     */
    private function getOrigin($referer)
    {
        $refererUrl = parse_url($referer, PHP_URL_HOST);
        if (!$referer) {
            return null;
        }

        if (!in_array($refererUrl, $this->allowedDomains)) {
            return null;
        }

        return $refererUrl;
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param                  $referer
     */
    private function saveOriginMultimediaObject(MultimediaObject $multimediaObject, $referer)
    {
        if (!$referer) {
            $referer = $this->defaultHost;
        }

        $multimediaObject->setProperty('origin', $referer);
        $this->dm->flush();
    }
}
