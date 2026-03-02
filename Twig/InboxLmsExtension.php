<?php

namespace Pumukit\LmsBundle\Twig;

use Pumukit\LmsBundle\Services\InboxLmsService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InboxLmsExtension extends AbstractExtension
{
    private $inboxLmsService;

    public function __construct(InboxLmsService $inboxLmsService)
    {
        $this->inboxLmsService = $inboxLmsService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('inbox_lms_upload_url', [$this, 'getInboxLmsUploadURL']),
        ];
    }

    public function getInboxLmsUploadURL(): string
    {
        return $this->inboxLmsService->inboxLmsUploadURL();
    }
}
