<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

use Pumukit\CoreBundle\Services\FrameAncestorsProviderInterface;

final class FrameAncestorsProvider implements FrameAncestorsProviderInterface
{
    public function __construct(private readonly array $domains) {}

    public function frameAncestors(): array
    {
        return $this->domains;
    }
}
