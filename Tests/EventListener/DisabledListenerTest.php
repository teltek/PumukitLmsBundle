<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Pumukit\LmsBundle\EventListener\DisabledListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 *
 * @coversNothing
 */
class DisabledListenerTest extends TestCase
{
    public function testItLeavesTheOtherControllersAlone(): void
    {
        $event = $this->event('Pumukit\WebTVBundle\Controller\IndexController::indexAction');

        (new DisabledListener())($event);

        $this->assertNull($event->getResponse());
    }

    public function testItGivesNotFoundForTheLmsControllers(): void
    {
        $this->expectException(NotFoundHttpException::class);

        (new DisabledListener())($this->event('Pumukit\LmsBundle\Controller\LmsController::iframeAction'));
    }

    private function event(string $controller): RequestEvent
    {
        $request = new Request();
        $request->attributes->set('_controller', $controller);

        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
