<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\Services;

use Pumukit\LmsBundle\Services\SSOService;
use Pumukit\LmsBundle\Tests\LmsTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;

/**
 * @internal
 *
 * @coversNothing
 */
class SSOServiceTest extends LmsTestCase
{
    public function testAUserKeepsItsNameWhenThereIsNoLdap(): void
    {
        $profile = new PermissionProfile();
        $profile->setName('Teacher');
        $this->dm->persist($profile);
        $this->dm->flush();

        $user = static::getContainer()->get(SSOService::class)->createUserByUsernameAndEmail('teacher', 'teacher@example.org', 'Ana Teacher', 'Teacher');

        $this->assertSame('Ana Teacher', $user->getFullname());
    }
}
