<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class FilterListener
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $req = $event->getRequest();
        $routeParams = $req->attributes->get('_route_params');

        /* TO-DO: Review - Multimedia Object not found
        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST
            && (false !== strpos($req->attributes->get('_controller'), 'LmsBundle'))
            && (!isset($routeParams['filter']) || $routeParams['filter'])) {
            $filter = $this->dm->getFilterCollection()->enable('frontend');
            $filter->setParameter('pub_channel_tag', array('$in' => array('PUCHWEBTV', 'PUCHOPENEDX', 'PUCHLMS', 'PUCHMOODLE')));
            $filter->setParameter('status', MultimediaObject::STATUS_PUBLISHED);
            $filter->setParameter('display_track_tag', 'display');
        }
        */
    }
}
