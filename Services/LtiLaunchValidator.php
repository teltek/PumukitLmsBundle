<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Pumukit\LmsBundle\Document\LTIClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class LtiLaunchValidator
{
    public const STATE = 'lti_state';
    public const CLAIM = 'https://purl.imsglobal.org/spec/lti/claim/';
    private const STATE_TTL = 600;

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly HttpClientInterface $httpClient,
        private readonly SignedToken $signedToken,
        private readonly string $environment,
    ) {}

    public function state(LTIClient $client): array
    {
        $nonce = bin2hex(random_bytes(16));

        return [$this->signedToken->issue(self::STATE, ['client' => $client->clientId(), 'nonce' => $nonce], self::STATE_TTL), $nonce];
    }

    public function validate(string $idToken, string $state): array
    {
        $stateData = $this->signedToken->consume(self::STATE, $state);
        if (null === $stateData) {
            throw new LtiException('Invalid or expired state');
        }

        $unverified = $this->unverifiedClaims($idToken);
        $audiences = (array) ($unverified['aud'] ?? []);
        $client = $this->client((string) ($unverified['iss'] ?? ''), $audiences);
        if ($client->clientId() !== $stateData['client']) {
            throw new LtiException('The token is not for the client of the login');
        }

        try {
            $jwks = $this->httpClient->request('GET', $client->getJWKSUri(), [
                'timeout' => 10,
                'verify_peer' => 'dev' !== $this->environment,
                'verify_host' => 'dev' !== $this->environment,
            ])->toArray();
            $claims = JWT::decode($idToken, JWK::parseKeySet($jwks, 'RS256'));
        } catch (\Throwable $exception) {
            throw new LtiException('Invalid token: '.$exception->getMessage(), 0, $exception);
        }

        $audiences = (array) $claims->aud;
        if (!in_array($client->clientId(), $audiences, true) || (count($audiences) > 1 && ($claims->azp ?? null) !== $client->clientId())) {
            throw new LtiException('Invalid audience');
        }
        if (!hash_equals($stateData['nonce'], (string) ($claims->nonce ?? ''))) {
            throw new LtiException('Invalid nonce');
        }
        $deploymentId = $client->ltiDeploymentId();
        if ($deploymentId && $deploymentId !== ($claims->{self::CLAIM.'deployment_id'} ?? null)) {
            throw new LtiException('Invalid deployment');
        }
        if ('1.3.0' !== ($claims->{self::CLAIM.'version'} ?? null)) {
            throw new LtiException('Invalid LTI version');
        }

        return [$client, $claims];
    }

    private function client(string $issuer, array $audiences): LTIClient
    {
        foreach ($this->documentManager->getRepository(LTIClient::class)->findBy(['issuer' => $issuer]) as $client) {
            if (in_array($client->clientId(), $audiences, true)) {
                return $client;
            }
        }

        throw new LtiException('Client not found');
    }

    private function unverifiedClaims(string $idToken): array
    {
        $parts = explode('.', $idToken);
        $claims = 3 === count($parts) ? json_decode((string) JWT::urlsafeB64Decode($parts[1]), true) : null;
        if (!is_array($claims)) {
            throw new LtiException('Malformed token');
        }

        return $claims;
    }
}
