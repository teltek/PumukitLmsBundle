<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Utils;

use Symfony\Component\HttpFoundation\Request;

final class RequestParameter
{
    public static function get(Request $request, string $name): mixed
    {
        foreach ([$request->attributes, $request->query, $request->request] as $bag) {
            if ($bag->has($name)) {
                return $bag->all()[$name];
            }
        }

        return null;
    }

    public static function getString(Request $request, string $name): ?string
    {
        $value = self::get($request, $name);

        return is_string($value) ? $value : null;
    }
}
