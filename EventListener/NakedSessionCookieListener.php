<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, priority: -1100)]
final class NakedSessionCookieListener
{
    public function __construct(private readonly string $nakedBackofficeDomain) {}

    public function __invoke(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || '' === $this->nakedBackofficeDomain || 0 !== strcasecmp($request->getHost(), (string) parse_url('//'.preg_replace('#^https?://#', '', $this->nakedBackofficeDomain), PHP_URL_HOST))) {
            return;
        }
        if (!$request->hasSession()) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $name = $request->getSession()->getName();
        foreach ($headers->getCookies() as $cookie) {
            if ($cookie->getName() !== $name || Cookie::SAMESITE_NONE === $cookie->getSameSite()) {
                continue;
            }
            $headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
            $headers->setCookie($cookie->withSameSite(Cookie::SAMESITE_NONE)->withSecure(true));
        }
    }
}
