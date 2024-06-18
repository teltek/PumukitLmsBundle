<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

class LTIKeyConfiguration
{
    private $kernelProjectDir;

    public function __construct(string $kernelProjectDir)
    {
        $this->kernelProjectDir = $kernelProjectDir;
    }

    public function privateKey(): string
    {
        return $this->kernelProjectDir.'/config/lti/keys/private.pem';
    }

    public function privateKeyContent(): string
    {
        return file_get_contents($this->kernelProjectDir.'/config/lti/keys/private.pem');
    }
}
