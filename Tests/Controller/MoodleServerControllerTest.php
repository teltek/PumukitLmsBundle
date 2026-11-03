<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\Controller;

use Pumukit\CoreBundle\Services\InboxService;
use Pumukit\LmsBundle\Services\UploadAuthorization;
use Pumukit\LmsBundle\Tests\LmsTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class MoodleServerControllerTest extends LmsTestCase
{
    private const SERVER = ['HTTPS' => 'on', 'HTTP_REFERER' => 'https://moodle.localhost/course', 'HTTP_TUS_RESUMABLE' => '1.0.0', 'HTTP_UPLOAD_LENGTH' => '10'];

    public function testAnUploadWithoutHashIsRejected(): void
    {
        $teacher = $this->user();
        $series = $this->ownedSeries($teacher);

        $this->client->request('POST', '/moodle/tus?series='.$series->getId().'&username='.$teacher->getUsername(), server: self::SERVER + ['HTTP_UPLOAD_METADATA' => 'filename '.base64_encode('video.mp4').',filetype '.base64_encode('video/mp4')]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAnUploadToAnotherSeriesIsRejected(): void
    {
        $teacher = $this->user();
        $others = $this->ownedSeries($this->user());

        $this->client->request('POST', '/moodle/tus?series='.$others->getId().'&username='.$teacher->getUsername().'&hash='.$this->hash($teacher->getUsername()), server: self::SERVER + ['HTTP_UPLOAD_METADATA' => 'filename '.base64_encode('video.mp4').',filetype '.base64_encode('video/mp4')]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testASignedUploadNeedsAValidSignature(): void
    {
        $teacher = $this->user();
        $series = $this->ownedSeries($teacher);
        [$userId, $expires] = self::getContainer()->get(UploadAuthorization::class)->sign($teacher);

        $this->client->request('POST', '/moodle/tus/lti/'.$userId.'/'.$expires.'/'.str_repeat('a', 64).'?series='.$series->getId(), server: self::SERVER + ['HTTP_UPLOAD_METADATA' => 'filename '.base64_encode('video.mp4').',filetype '.base64_encode('video/mp4')]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testTheImportOfASignedUploadNeedsTheSameUser(): void
    {
        $teacher = $this->user();
        $inbox = realpath(self::getContainer()->get(InboxService::class)->inboxPath());
        @mkdir($inbox.'/_moodle_map', 0755, true);
        $uuid = 'test-'.bin2hex(random_bytes(4));
        file_put_contents($inbox.'/_moodle_map/'.$uuid.'.json', json_encode(['series' => 'abc', 'userId' => $teacher->getId(), 'authType' => 'lti', 'fileName' => 'video.mp4', 'uploadLength' => 10]));

        $this->client->loginUser($this->user(), 'pumukit');
        $this->client->request('POST', '/moodle/dispatchImport', ['uuid' => $uuid, 'fileName' => 'video.mp4']);

        $this->assertResponseStatusCodeSame(401);
        unlink($inbox.'/_moodle_map/'.$uuid.'.json');
    }
}
