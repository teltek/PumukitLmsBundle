<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

class InboxLmsService
{
    private $lmsUploadURL;

    public function __construct(string $lmsUploadURL)
    {
        $this->lmsUploadURL = $lmsUploadURL;
    }

    public function inboxLmsUploadURL(): string
    {
        return $this->lmsUploadURL;
    }
}
