<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

final class LtiRoles
{
    private const PRIVILEGED = ['Instructor', 'ContentDeveloper', 'Administrator'];

    public static function isPrivileged(array $roles): bool
    {
        foreach ($roles as $role) {
            $name = (string) $role;
            $name = str_contains($name, '#') ? substr($name, strrpos($name, '#') + 1) : $name;
            if (in_array($name, self::PRIVILEGED, true)) {
                return true;
            }
        }

        return false;
    }
}
