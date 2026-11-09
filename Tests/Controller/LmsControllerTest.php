<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\Controller;

use Pumukit\LmsBundle\Document\LTIClient;
use Pumukit\LmsBundle\Tests\LmsTestCase;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * @internal
 *
 * @coversNothing
 */
class LmsControllerTest extends LmsTestCase
{
    public function testTheEmbedsOfUnknownObjectsGiveNotFound(): void
    {
        $server = ['HTTP_REFERER' => 'https://moodle.localhost/course'];

        $this->client->request('GET', '/openedx/openedx/embed/not-an-id', server: $server);
        $this->assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/openedx/openedx/playlist/embed/0123456789abcdef01234567?hash='.$this->hash(''), server: $server);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheSearchPageHasNoInlineScripts(): void
    {
        $root = new Tag();
        $root->setCod('ROOT');
        $this->dm->persist($root);
        $unesco = new Tag();
        $unesco->setCod('UNESCO');
        $unesco->setParent($root);
        $this->dm->persist($unesco);
        $this->dm->flush();

        $crawler = $this->client->request('GET', '/openedx/search/public/multimediaobjects');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('[data-controller="lms--search"] #sharedVideosModal'));
        $this->assertCount(0, $crawler->filter('script:not([src]):not([type="importmap"]):not([type="module"]), [onclick], [data-toggle]'));
    }

    public function testTheSessionCookieOfTheNakedDomainCanBeUsedInsideTheLmsIframe(): void
    {
        $this->dm->persist(LTIClient::create([
            'openid_configuration' => 'https://moodle.localhost/openid',
            'registration_token' => 'token',
            'issuer' => 'https://moodle.localhost',
            'token_endpoint' => 'https://moodle.localhost/token',
            'authorization_endpoint' => 'https://moodle.localhost/auth',
            'registration_endpoint' => 'https://moodle.localhost/register',
            'openIdRaw' => json_encode(['jwks_uri' => 'https://moodle.localhost/certs']),
            'client_id' => 'client-1',
            'lti_deployment_id' => '1',
            'registerRaw' => json_encode(['https://purl.imsglobal.org/spec/lti-tool-configuration' => ['target_link_uri' => 'https://lms.localhost/lti/launch']]),
        ]));
        $this->dm->flush();

        foreach (['https://lms.localhost' => Cookie::SAMESITE_NONE, 'https://localhost' => Cookie::SAMESITE_LAX] as $host => $sameSite) {
            $this->client->getCookieJar()->clear();
            $this->client->request('POST', $host.'/lti/login', ['client_id' => 'client-1', 'login_hint' => '1']);

            $cookies = array_values(array_filter($this->client->getResponse()->headers->getCookies(), static fn (Cookie $cookie): bool => in_array($cookie->getName(), ['pumukit', 'MOCKSESSID'], true)));
            $this->assertCount(1, $cookies, $host);
            $this->assertSame($sameSite, $cookies[0]->getSameSite(), $host);
        }
    }
}
