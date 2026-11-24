<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\Controller;

use Pumukit\LmsBundle\Tests\LmsTestCase;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 *
 * @coversNothing
 */
class SearchControllerTest extends LmsTestCase
{
    private Tag $webTv;
    private Tag $category;
    private Series $series;

    public function setUp(): void
    {
        parent::setUp();

        $root = $this->tag('ROOT');
        $unesco = $this->tag('UNESCO', $root);
        $this->category = $this->tag('U310000', $unesco);
        $this->webTv = $this->tag(PumukitWebTVBundle::WEB_TV_TAG, $root);
        $this->series = self::getContainer()->get(FactoryService::class)->createSeries(null, ['en' => 'Course']);
    }

    public function testTheSeriesSearchOfTheWebTVKeepsItsOwnPath(): void
    {
        $this->assertSame('/searchseries', self::getContainer()->get(RouterInterface::class)->generate('pumukit_webtv_search_series'));

        $this->client->request('GET', '/searchseries');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/openedx/searchseries');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheMultimediaObjectsSearchOfTheWebTVIsNotChanged(): void
    {
        $this->assertSame('/searchmultimediaobjects', self::getContainer()->get(RouterInterface::class)->generate('pumukit_webtv_search_multimediaobjects'));
    }

    public function testOnlyThePublicVideosOfTheWebTVAreListed(): void
    {
        $this->video('Public video');
        $this->video('Video for logged in users', EmbeddedBroadcast::TYPE_LOGIN);
        $this->video('Blocked video', EmbeddedBroadcast::TYPE_PUBLIC, MultimediaObject::STATUS_BLOCKED);
        $this->video('Video out of the WebTV', EmbeddedBroadcast::TYPE_PUBLIC, MultimediaObject::STATUS_PUBLISHED, false);

        foreach (['/openedx/search/public/multimediaobjects', '/openedx/searchmultimediaobjects'] as $url) {
            $this->client->request('GET', $url);

            $this->assertResponseIsSuccessful();
            $this->assertSame(['Public video'], $this->listedTitles(), $url);
        }
    }

    public function testTheVideosCanBeFilteredByCategory(): void
    {
        $this->video('Video of the category', category: true);
        $this->video('Video of another category');

        $this->client->request('GET', '/openedx/search/public/multimediaobjects', ['tags' => [$this->category->getCod()]]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['Video of the category'], $this->listedTitles());
    }

    public function testTheSearchCanBeBlockedToACategory(): void
    {
        $this->video('Video of the category', category: true);
        $this->video('Video of another category');

        $this->client->request('GET', '/openedx/searchmultimediaobjects/'.$this->category->getCod());

        $this->assertResponseIsSuccessful();
        $this->assertSame(['Video of the category'], $this->listedTitles());
    }

    public function testTheSearchTextCanBeBlankOrHaveWildcards(): void
    {
        $this->video('Public video');
        $this->video('Another video');

        $this->client->request('GET', '/openedx/search/public/multimediaobjects', ['search' => '   ']);
        $this->assertResponseIsSuccessful();
        $this->assertSame(['Another video', 'Public video'], $this->listedTitles());

        $this->client->request('GET', '/openedx/search/public/multimediaobjects', ['search' => 'Pub*']);
        $this->assertResponseIsSuccessful();
        $this->assertSame(['Public video'], $this->listedTitles());
    }

    private function listedTitles(): array
    {
        $titles = $this->client->getCrawler()->filter('a.multimediaobject')->each(static fn ($link): string => trim((string) $link->attr('title')));
        $titles = array_values(array_unique($titles));
        sort($titles);

        return $titles;
    }

    private function tag(string $cod, ?Tag $parent = null): Tag
    {
        $tag = new Tag();
        $tag->setCod($cod);
        $tag->setTitle($cod);
        $tag->setDisplay(true);
        if ($parent) {
            $tag->setParent($parent);
        }
        $this->dm->persist($tag);
        $this->dm->flush();

        return $tag;
    }

    private function video(string $title, string $broadcast = EmbeddedBroadcast::TYPE_PUBLIC, int $status = MultimediaObject::STATUS_PUBLISHED, bool $webTv = true, bool $category = false): void
    {
        $multimediaObject = self::getContainer()->get(FactoryService::class)->createMultimediaObject($this->series);
        $multimediaObject->setTitle($title);
        $multimediaObject->setStatus($status);
        if ($webTv) {
            $multimediaObject->addTag($this->webTv);
        }
        if ($category) {
            $multimediaObject->addTag($this->category);
        }
        $multimediaObject->addTrack(Track::create(
            'video.mp4',
            i18nText::create(['en' => '']),
            'en',
            Tags::create(['display']),
            false,
            false,
            0,
            Storage::create(StorageUrl::create(''), Path::create(self::getContainer()->getParameter('kernel.project_dir').'/tests/files/pumukit.mp4')),
            VideoAudio::create('{"format":{"duration":"10.000000"}}')
        ));
        self::getContainer()->get(EmbeddedBroadcastService::class)->setByType($multimediaObject, $broadcast, false);
        $this->dm->flush();
    }
}
