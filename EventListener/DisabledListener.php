<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsEventListener]
final class DisabledListener
{
    public function __construct(private readonly bool $enabled) {}

    public function __invoke(ControllerEvent $event): void
    {
        $controller = $event->getController();
        $class = is_array($controller) ? $controller[0]::class : $controller::class;
        if (!$this->enabled && str_starts_with($class, 'Pumukit\LmsBundle\Controller\\')) {
            throw new NotFoundHttpException();
        }
    }
}
