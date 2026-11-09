<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsEventListener(priority: 16)]
final class DisabledListener
{
    public function __invoke(RequestEvent $event): void
    {
        $controller = $event->getRequest()->attributes->get('_controller');
        if (is_string($controller) && str_starts_with($controller, 'Pumukit\LmsBundle\Controller\\')) {
            throw new NotFoundHttpException();
        }
    }
}
