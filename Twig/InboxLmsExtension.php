<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Twig;

use Pumukit\LmsBundle\Services\InboxLmsService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InboxLmsExtension extends AbstractExtension
{
    private InboxLmsService $inboxLmsService;
    private RequestStack $requestStack;
    private Security $security;
    private string $secret;

    public function __construct(
        InboxLmsService $inboxLmsService,
        RequestStack $requestStack,
        Security $security,
        string $secret
    ) {
        $this->inboxLmsService = $inboxLmsService;
        $this->requestStack = $requestStack;
        $this->security = $security;
        $this->secret = $secret;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('inbox_lms_upload_url', [$this, 'getInboxLmsUploadURL']),
        ];
    }

    public function getInboxLmsUploadURL(): string
    {
        $url = $this->inboxLmsService->inboxLmsUploadURL();
        $request = $this->requestStack->getCurrentRequest();
        $user = $this->security->getUser();

        if (!$request || !$user || !$request->hasPreviousSession()) {
            return $url;
        }

        $session = $request->getSession();

        if (!$session->has('lti_client_id')) {
            return $url;
        }

        $userId = (string) $user->getId();
        $expires = time() + 3600;

        $payload = 'lti_upload:'.$userId.':'.$expires;
        $signature = hash_hmac('sha256', $payload, $this->secret);

        return rtrim($url, '/')
            .'/lti/'
            .rawurlencode($userId)
            .'/'
            .$expires
            .'/'
            .$signature;
    }
}
