<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\LmsBundle\Services\ConfigurationService;
use Pumukit\LmsBundle\Services\LTIRegister;
use Pumukit\LmsBundle\Tests\LmsTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversNothing
 */
class LTIAutoRegisterControllerTest extends LmsTestCase
{
    private const OPENID_URL = 'https://lms.example.org/openid';

    private array $requests = [];

    #[DataProvider('forbiddenUrls')]
    public function testNoRequestLeavesForAnUntrustedUrl(string $url): void
    {
        $this->useLms(['lms\.example\.org'], []);

        $this->register($url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertSame([], $this->requests);
    }

    public static function forbiddenUrls(): iterable
    {
        yield 'another domain' => ['https://evil.example.com/openid'];

        yield 'a domain that ends the same' => ['https://notlms.example.org/openid'];

        yield 'a domain that starts the same' => ['https://lms.example.org.evil.com/openid'];

        yield 'without https' => ['http://lms.example.org/openid'];

        yield 'another scheme' => ['file:///etc/passwd'];

        yield 'an internal address' => ['https://169.254.169.254/latest/meta-data'];

        yield 'with a user' => ['https://lms.example.org@evil.example.com/openid'];

        yield 'with a backslash' => ['https://evil.example.com\@lms.example.org/openid'];
    }

    public function testASubdomainOfAnAllowedDomainIsRead(): void
    {
        $this->useLms(['blackboard\.com'], [new MockResponse('[]')]);

        $this->register('https://developer.blackboard.com/api/openid?registrationToken=token');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame(['https://developer.blackboard.com/api/openid?registrationToken=token'], $this->requests);
    }

    public function testARedirectIsNotFollowed(): void
    {
        $this->useLms(['lms\.example\.org'], [new MockResponse('', ['http_code' => 302, 'response_headers' => ['Location: https://169.254.169.254/']])]);

        $this->register(self::OPENID_URL);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame([self::OPENID_URL], $this->requests);
    }

    public function testAnAnswerThatIsNotAnObjectIsABadRequest(): void
    {
        $this->useLms(['lms\.example\.org'], [new MockResponse('null')]);

        $this->register(self::OPENID_URL);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testAnotherIssuerIsRejectedBeforeSendingTheToken(): void
    {
        $this->useLms(['lms\.example\.org'], [$this->openIdResponse('https://evil.example.com', 'https://lms.example.org/register')]);

        $this->register(self::OPENID_URL);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertSame([self::OPENID_URL], $this->requests);
    }

    public function testAnotherRegistrationEndpointIsRejectedBeforeSendingTheToken(): void
    {
        $this->useLms(['lms\.example\.org'], [$this->openIdResponse('https://lms.example.org', 'https://evil.example.com/register')]);

        $this->register(self::OPENID_URL);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertSame([self::OPENID_URL], $this->requests);
    }

    public function testARegistrationThatFailsIsABadRequest(): void
    {
        $this->useLms(['lms\.example\.org'], [
            $this->openIdResponse('https://lms.example.org', 'https://lms.example.org/register'),
            new MockResponse('', ['http_code' => 500]),
        ]);

        $this->register(self::OPENID_URL);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testAnAllowedLmsIsRegistered(): void
    {
        $this->useLms(['lms\.example\.org'], [
            $this->openIdResponse('https://lms.example.org', 'https://lms.example.org/register'),
            new MockResponse((string) json_encode([
                'client_id' => 'client',
                'https://purl.imsglobal.org/spec/lti-tool-configuration' => ['deployment_id' => '1'],
            ])),
        ]);

        $this->register(self::OPENID_URL);

        $this->assertResponseIsSuccessful();
        $this->assertSame([self::OPENID_URL, 'https://lms.example.org/register'], $this->requests);
    }

    public function testAnyDomainDoesNotOpenTheInternalAddresses(): void
    {
        $this->useLms(['*'], [new MockResponse('[]')]);

        $this->register('https://169.254.169.254/latest/meta-data');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame([], $this->requests);
    }

    private function register(string $url): void
    {
        $this->client->request('GET', '/lti/register?'.http_build_query(['openid_configuration' => $url, 'registration_token' => 'token']));
    }

    private function openIdResponse(string $issuer, string $registrationEndpoint): MockResponse
    {
        return new MockResponse((string) json_encode([
            'issuer' => $issuer,
            'registration_endpoint' => $registrationEndpoint,
            'token_endpoint' => $issuer.'/token',
            'authorization_endpoint' => $issuer.'/authorize',
        ]));
    }

    private function useLms(array $domains, array $responses): void
    {
        $container = static::getContainer();
        $configuration = $container->get(ConfigurationService::class);
        (new \ReflectionProperty($configuration, 'domainsPatterns'))->setValue($configuration, $domains);

        $httpClient = new MockHttpClient(function (string $method, string $url) use (&$responses): MockResponse {
            $this->requests[] = $url;

            return array_shift($responses) ?? new MockResponse('', ['http_code' => 500]);
        });
        $container->set(LTIRegister::class, new LTIRegister($this->dm, $httpClient, $configuration, 'pumukit.example.org'));
    }
}
