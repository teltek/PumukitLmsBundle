<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Pumukit\LmsBundle\Services\ConfigurationService;
use Pumukit\LmsBundle\Services\LTIKeyConfiguration;
use Pumukit\LmsBundle\Services\LTIRegister;
use Pumukit\LmsBundle\Tests\LmsTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversNothing
 */
class InvalidRequestsTest extends LmsTestCase
{
    #[DataProvider('invalidRequests')]
    public function testAnInvalidRequestIsNotAServerError(string $url, int $status): void
    {
        $this->client->request('GET', $url);

        $this->assertResponseStatusCodeSame($status);
    }

    public static function invalidRequests(): iterable
    {
        yield 'registration without configuration' => ['/lti/register', Response::HTTP_BAD_REQUEST];

        yield 'registration with an array' => ['/lti/register?openid_configuration[]=x', Response::HTTP_BAD_REQUEST];

        yield 'registration without token' => ['/lti/register?openid_configuration=https://lms.example.org', Response::HTTP_BAD_REQUEST];

        yield 'registration of blackboard without token' => ['/lti/register?openid_configuration=https://blackboard.example.org', Response::HTTP_BAD_REQUEST];

        yield 'deep link without id' => ['/lti/deep_link', Response::HTTP_BAD_REQUEST];

        yield 'deep link with a bad id' => ['/lti/deep_link?id=bad', Response::HTTP_NOT_FOUND];

        yield 'deep link of a missing video' => ['/lti/deep_link?id=aaaaaaaaaaaaaaaaaaaaaaaa', Response::HTTP_NOT_FOUND];

        yield 'manager without parameters' => ['/openedx/sso/manager', Response::HTTP_NOT_FOUND];

        yield 'manager with arrays' => ['/openedx/sso/manager?email[]=x&username[]=y&hash[]=z', Response::HTTP_NOT_FOUND];

        yield 'manager without referer' => ['/openedx/sso/manager?email=a@example.org&hash=x', Response::HTTP_NOT_FOUND];

        yield 'upload without parameters' => ['/openedx/sso/upload', Response::HTTP_NOT_FOUND];

        yield 'upload without hash' => ['/openedx/sso/upload?username=someone', Response::HTTP_NOT_FOUND];

        yield 'personal recorder without parameters' => ['/openedx/sso/personal_recorder', Response::HTTP_NOT_FOUND];
    }

    public function testAConfigurationThatCannotBeReadIsABadRequest(): void
    {
        $register = $this->createStub(LTIRegister::class);
        $register->method('openIdRequestConfiguration')->willThrowException(new \RuntimeException('Unreachable'));
        static::getContainer()->set(LTIRegister::class, $register);
        $configuration = static::getContainer()->get(ConfigurationService::class);
        (new \ReflectionProperty($configuration, 'domainsPatterns'))->setValue($configuration, ['lms\.invalid']);

        $this->client->request('GET', '/lti/register?openid_configuration=https://lms.invalid/openid&registration_token=token');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testARefererThatIsNotAnAddressIsNotAServerError(): void
    {
        $this->client->request('GET', '/openedx/sso/manager?email=a@example.org&hash=x', [], [], ['HTTP_REFERER' => 'nonsense']);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testTheKeysOfAnInstallationWithoutThemAreNotFound(): void
    {
        static::getContainer()->set(LTIKeyConfiguration::class, new LTIKeyConfiguration('/nowhere'));

        $this->client->request('GET', '/.well-known/lti/jwks.json');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
