<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Twig;

use Pumukit\LmsBundle\Services\InboxLmsService;
use Pumukit\LmsBundle\Services\UploadAuthorization;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InboxLmsExtension extends AbstractExtension
{
    public function __construct(
        private readonly InboxLmsService $inboxLmsService,
        private readonly Security $security,
        private readonly UploadAuthorization $uploadAuthorization,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('inbox_lms_upload_url', [$this, 'getInboxLmsUploadURL']),
        ];
    }

    public function getInboxLmsUploadURL(): string
    {
        $url = $this->inboxLmsService->inboxLmsUploadURL();
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $url;
        }

        [$userId, $expires, $signature] = $this->uploadAuthorization->sign($user);

        return rtrim($url, '/').'/lti/'.rawurlencode($userId).'/'.$expires.'/'.$signature;
    }
}
