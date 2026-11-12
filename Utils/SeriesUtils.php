<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Utils;

class SeriesUtils
{
    public static function buildParams(): string
    {
        return http_build_query(['externalData' => ['mmobjData' => ['properties' => ['openedx' => true]]]]);
    }
}
