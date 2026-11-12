<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\Controller;

use Pumukit\LmsBundle\Controller\LTIRecorderController;
use Pumukit\LmsBundle\Services\SignedToken;
use Pumukit\LmsBundle\Tests\LmsTestCase;
use Pumukit\SchemaBundle\Document\Series;

/**
 * @internal
 *
 * @coversNothing
 */
class LTIRecorderControllerTest extends LmsTestCase
{
    public function testTheRecorderLinkLogsTheUserInOnce(): void
    {
        $teacher = $this->user();
        $signedToken = self::getContainer()->get(SignedToken::class);
        $token = $signedToken->issue(LTIRecorderController::TOKEN, ['user' => (string) $teacher->getId()], LTIRecorderController::TTL);

        $this->client->request('GET', '/lti/recorder/'.$token);
        $this->assertResponseRedirects();
        $this->assertStringStartsWith('/admin/personalrecorder/?', $this->client->getResponse()->headers->get('Location'));
        $this->assertStringNotContainsString('series', $this->client->getResponse()->headers->get('Location'));
        $this->assertSame(0, $this->dm->getRepository(Series::class)->count());

        $this->client->request('GET', '/lti/recorder/'.$token);
        $this->assertResponseStatusCodeSame(403);

        $this->client->request('GET', '/lti/recorder/'.$signedToken->issue('other', ['user' => (string) $teacher->getId()], 60));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTheSignedTokenIsSingleUseAndBoundToItsPurpose(): void
    {
        $signedToken = self::getContainer()->get(SignedToken::class);
        $token = $signedToken->issue('purpose', ['a' => 1], 60);

        $this->assertNull($signedToken->consume('other', $token));
        $this->assertSame(['a' => 1], $signedToken->consume('purpose', $token));
        $this->assertNull($signedToken->consume('purpose', $token));
        $this->assertNull($signedToken->consume('purpose', $token.'x'));
        $this->assertNull($signedToken->consume('purpose', $signedToken->issue('purpose', [], -1)));
    }
}
