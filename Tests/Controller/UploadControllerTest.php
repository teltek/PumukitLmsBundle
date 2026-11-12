<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\Controller;

use Pumukit\LmsBundle\Tests\LmsTestCase;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\PersonalSeriesService;

/**
 * @internal
 *
 * @coversNothing
 */
class UploadControllerTest extends LmsTestCase
{
    public function testTheUploadGoesToThePersonalSeriesCreatedOnce(): void
    {
        $teacher = $this->user();
        $this->client->loginUser($teacher, 'pumukit');

        $this->client->request('GET', '/openedx/sso/upload');
        $this->assertResponseRedirects();
        $first = $this->client->getResponse()->headers->get('Location');

        $this->client->request('GET', '/openedx/sso/upload');
        $this->assertSame($first, $this->client->getResponse()->headers->get('Location'));

        $this->dm->clear();
        $series = $this->dm->getRepository(Series::class)->findAll();
        $this->assertCount(1, $series);
        $this->assertTrue($series[0]->getProperty(PersonalSeriesService::DEFAULT_PERSONAL_SERIES_PROPERTY));
        $this->assertStringContainsString('/'.$series[0]->getId().'/upload', $first);
        $this->assertSame($series[0]->getId(), $this->dm->getRepository(User::class)->find($teacher->getId())->getPersonalSeries());
    }
}
