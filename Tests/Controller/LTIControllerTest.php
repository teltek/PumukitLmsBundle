<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\Controller;

use Firebase\JWT\JWT;
use Pumukit\LmsBundle\Document\LTIClient;
use Pumukit\LmsBundle\Services\LtiLaunchValidator;
use Pumukit\LmsBundle\Services\SignedToken;
use Pumukit\LmsBundle\Tests\LmsTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @internal
 *
 * @coversNothing
 */
class LTIControllerTest extends LmsTestCase
{
    private const ISSUER = 'https://moodle.example.org';
    private const CLIENT_ID = 'client-1';
    private const DEPLOYMENT = 'deployment-1';
    private const KEY_ID = 'moodle-key';
    private const INSTRUCTOR = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor';
    private const LEARNER = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner';

    private static ?string $privateKey = null;

    public function setUp(): void
    {
        parent::setUp();

        foreach (['Auto Publisher' => PermissionProfile::SCOPE_PERSONAL, 'Viewer' => PermissionProfile::SCOPE_NONE] as $name => $scope) {
            $profile = new PermissionProfile();
            $profile->setName($name);
            $profile->setScope($scope);
            $this->dm->persist($profile);
        }
        $this->dm->persist(LTIClient::create([
            'openid_configuration' => self::ISSUER.'/openid',
            'registration_token' => 'token',
            'issuer' => self::ISSUER,
            'token_endpoint' => self::ISSUER.'/token',
            'authorization_endpoint' => self::ISSUER.'/auth',
            'registration_endpoint' => self::ISSUER.'/register',
            'openIdRaw' => json_encode(['jwks_uri' => self::ISSUER.'/certs']),
            'client_id' => self::CLIENT_ID,
            'lti_deployment_id' => self::DEPLOYMENT,
            'registerRaw' => json_encode(['https://purl.imsglobal.org/spec/lti-tool-configuration' => ['target_link_uri' => 'https://localhost/lti/launch']]),
        ]));
        $this->dm->flush();

        $details = openssl_pkey_get_details(openssl_pkey_get_private(self::privateKey()));
        $jwks = ['keys' => [['kty' => 'RSA', 'kid' => self::KEY_ID, 'use' => 'sig', 'n' => JWT::urlsafeB64Encode($details['rsa']['n']), 'e' => JWT::urlsafeB64Encode($details['rsa']['e'])]]];
        $container = self::getContainer();
        $container->set(LtiLaunchValidator::class, new LtiLaunchValidator(
            $this->dm,
            new MockHttpClient(static fn (): MockResponse => new MockResponse(json_encode($jwks))),
            $container->get(SignedToken::class),
            'test',
        ));
    }

    public function testTheLoginNeedsAKnownClient(): void
    {
        $this->client->request('POST', '/lti/login', ['client_id' => 'unknown', 'login_hint' => '1']);
        $this->assertResponseStatusCodeSame(400);

        $crawler = $this->client->request('POST', '/lti/login', ['client_id' => self::CLIENT_ID, 'login_hint' => '1', 'iss' => self::ISSUER]);
        $this->assertResponseIsSuccessful();
        $this->assertSame(self::ISSUER.'/auth', $crawler->filter('form')->attr('action'));
        $this->assertNotEmpty($crawler->filter('input[name="state"]')->attr('value'));
    }

    public function testAnInstructorIsLoggedInAndTheStateIsUsedOnce(): void
    {
        [$state, $nonce] = $this->state();
        $token = $this->token(['nonce' => $nonce]);

        $this->client->request('POST', '/lti/launch', ['id_token' => $token, 'state' => $state]);
        $this->assertResponseRedirects('/admin/series/');
        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'ada']);
        $this->assertSame('Auto Publisher', $user->getPermissionProfile()->getName());

        $this->client->request('POST', '/lti/launch', ['id_token' => $token, 'state' => $state]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testTheInvalidLaunchesAreRejected(): void
    {
        $cases = [
            'other audience' => ['aud' => 'other-client'],
            'other nonce' => ['nonce' => 'other'],
            'other deployment' => ['https://purl.imsglobal.org/spec/lti/claim/deployment_id' => 'other'],
            'expired' => ['exp' => time() - 60],
        ];

        foreach ($cases as $case => $claims) {
            [$state, $nonce] = $this->state();
            $this->client->request('POST', '/lti/launch', ['id_token' => $this->token($claims + ['nonce' => $nonce]), 'state' => $state]);
            $this->assertResponseStatusCodeSame(401, $case);
            $this->assertStringNotContainsString('No more response', (string) $this->client->getResponse()->getContent(), $case);
        }

        [, $nonce] = $this->state();
        $this->client->request('POST', '/lti/launch', ['id_token' => $this->token(['nonce' => $nonce]), 'state' => 'forged.state']);
        $this->assertResponseStatusCodeSame(401);
        $this->assertNull($this->dm->getRepository(User::class)->findOneBy(['username' => 'ada']));
    }

    public function testALearnerWatchesWithAFreshHashWithoutBeingCreated(): void
    {
        [$state, $nonce] = $this->state();
        $this->client->request('POST', '/lti/launch', ['id_token' => $this->token([
            'nonce' => $nonce,
            'https://purl.imsglobal.org/spec/lti/claim/roles' => [self::LEARNER],
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest',
            'https://purl.imsglobal.org/spec/lti/claim/target_link_uri' => 'https://localhost/openedx/openedx/embed/0123456789abcdef01234567?hash=old',
        ]), 'state' => $state]);

        $this->assertResponseRedirects('/openedx/openedx/embed/0123456789abcdef01234567?hash='.$this->hash(''));
        $this->assertNull($this->dm->getRepository(User::class)->findOneBy(['username' => 'ada']));

        [$state, $nonce] = $this->state();
        $this->client->request('POST', '/lti/launch', ['id_token' => $this->token(['nonce' => $nonce, 'https://purl.imsglobal.org/spec/lti/claim/roles' => [self::LEARNER]]), 'state' => $state]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTheResourceLinkMustBeOfThisSite(): void
    {
        [$state, $nonce] = $this->state();
        $this->client->request('POST', '/lti/launch', ['id_token' => $this->token([
            'nonce' => $nonce,
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest',
            'https://purl.imsglobal.org/spec/lti/claim/target_link_uri' => 'https://evil.example.org/',
        ]), 'state' => $state]);

        $this->assertResponseStatusCodeSame(400);
    }

    private function state(): array
    {
        return self::getContainer()->get(LtiLaunchValidator::class)->state($this->dm->getRepository(LTIClient::class)->findOneBy(['client_id' => self::CLIENT_ID]));
    }

    private function token(array $claims): string
    {
        $payload = array_merge([
            'iss' => self::ISSUER,
            'aud' => self::CLIENT_ID,
            'sub' => '42',
            'iat' => time(),
            'exp' => time() + 300,
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
            'https://purl.imsglobal.org/spec/lti/claim/deployment_id' => self::DEPLOYMENT,
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiDeepLinkingRequest',
            'https://purl.imsglobal.org/spec/lti/claim/roles' => [self::INSTRUCTOR],
            'https://purl.imsglobal.org/spec/lti/claim/custom' => ['userID' => '42', 'username' => 'ada', 'person_email' => 'ada@example.com', 'person_fullname' => 'Ada Lovelace'],
        ], $claims);

        return JWT::encode($payload, self::privateKey(), 'RS256', self::KEY_ID);
    }

    private static function privateKey(): string
    {
        if (null === self::$privateKey) {
            openssl_pkey_export(openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]), $pem);
            self::$privateKey = $pem;
        }

        return self::$privateKey;
    }
}
