<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\Services;

use Pumukit\LmsBundle\Services\UploadAuthorization;
use Pumukit\LmsBundle\Tests\LmsTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;

/**
 * @internal
 *
 * @coversNothing
 */
class UploadAuthorizationTest extends LmsTestCase
{
    public function testASignedUploadBelongsToItsUser(): void
    {
        $authorization = self::getContainer()->get(UploadAuthorization::class);
        $user = $this->user();
        [$userId, $expires, $signature] = $authorization->sign($user);

        $this->assertSame($user->getId(), $authorization->verify($userId, (string) $expires, $signature)?->getId());
        $this->assertNull($authorization->verify($userId, (string) $expires, str_repeat('0', 64)));
        $this->assertNull($authorization->verify($this->user()->getId(), (string) $expires, $signature));
        $this->assertNull($authorization->verify($userId, (string) (time() - 1), $signature));
    }

    public function testTheUserCanOnlyUploadToTheirSeries(): void
    {
        $authorization = self::getContainer()->get(UploadAuthorization::class);
        $teacher = $this->user();
        $other = $this->user();
        $own = $this->ownedSeries($teacher);
        $others = $this->ownedSeries($other);
        $lms = $this->lmsSeries();
        $personal = $this->ownedSeries($other, 'Personal');
        $teacher->setPersonalSeries($personal->getId());
        $this->dm->flush();

        $this->assertTrue($authorization->canUploadTo($teacher, $own->getId()));
        $this->assertTrue($authorization->canUploadTo($teacher, $lms->getId()));
        $this->assertTrue($authorization->canUploadTo($teacher, $personal->getId()));
        $this->assertFalse($authorization->canUploadTo($teacher, $others->getId()));
        $this->assertFalse($authorization->canUploadTo($teacher, 'not-an-id'));
        $this->assertFalse($authorization->canUploadTo($teacher, '0123456789abcdef01234567'));
        $this->assertTrue($authorization->canUploadTo($this->user(PermissionProfile::SCOPE_GLOBAL), $others->getId()));
    }
}
