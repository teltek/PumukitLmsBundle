<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\LmsBundle\Document\LTIClient;
use Pumukit\LmsBundle\PumukitLmsBundle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LTIRegister
{
    private $documentManager;
    private $client;
    private $verify_peer;
    private $domain;

    public function __construct(DocumentManager $documentManager, HttpClientInterface $httpClient, string $domain)
    {
        $this->documentManager = $documentManager;
        $this->client = $httpClient;
        $this->verify_peer = 'dev' !== getenv('APP_ENV');
        $this->domain = $domain;
    }

    public function createRegistrationEntry(array $params, array $openIdConfiguration, array $registration)
    {
        $data['openid_configuration'] = $params['openid_configuration'];
        $data['registration_token'] = $params['registration_token'];
        $data['issuer'] = $openIdConfiguration['issuer'];
        $data['token_endpoint'] = $openIdConfiguration['token_endpoint'];
        $data['authorization_endpoint'] = $openIdConfiguration['authorization_endpoint'];
        $data['registration_endpoint'] = $openIdConfiguration['registration_endpoint'];
        $data['openIdRaw'] = json_encode($openIdConfiguration);
        $data['client_id'] = $registration['client_id'];
        $data['lti_deployment_id'] = $registration['https://purl.imsglobal.org/spec/lti-tool-configuration']['deployment_id'];
        $data['registerRaw'] = json_encode($registration);

        $ltiClient = LTIClient::create($data);
        $this->documentManager->persist($ltiClient);
        $this->documentManager->flush();

        return $data;
    }

    public function openIdRequestConfiguration(string $openIdConfiguration)
    {
        $response = $this->client->request(
            'GET',
            $openIdConfiguration,
            [
                'verify_peer' => $this->verify_peer,
            ]
        );

        $responseResult = json_decode($response->getContent(), true);
        $this->validateOpenIdResult($responseResult);

        return $responseResult;
    }

    public function register(string $endpoint, string $token)
    {
        $response = $this->client->request(
            'POST',
            $endpoint,
            [
                'headers' => [
                    'Content-Type: application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
                'verify_peer' => $this->verify_peer,
                'body' => $this->createRegistrationJSON(),
            ]
        );

        return json_decode($response->getContent(), true);
    }

    private function compoundToolUrl(): string
    {
        if (str_contains($this->domain, 'https://')) {
            return $this->domain;
        }

        return 'https://'.$this->domain;
    }

    private function compoundToolUrlWithoutDomain(): string
    {
        $parsedUrl = parse_url($this->compoundToolUrl());

        return $parsedUrl['host'];
    }

    private function validateOpenIdResult(array $responseResult): void
    {
        if (!isset($responseResult['registration_endpoint'])) {
            throw new \Exception('Missing OpenId required configuration.');
        }
    }

    private function createRegistrationJSON(): string
    {
        $json['application_type'] = 'web';
        $json['response_types'] = ['id_token'];
        $json['grant_types'] = ['implicit', 'client_credentials'];
        $json['initiate_login_uri'] = $this->compoundToolUrl().PumukitLmsBundle::LTI_TOOL_LOGIN_URL;
        $json['redirect_uris'] = [
            $this->compoundToolUrl().PumukitLmsBundle::LTI_TOOL_LOGIN_URL,
            $this->compoundToolUrl().PumukitLmsBundle::LTI_TOOL_LAUNCH_URL,
            $this->compoundToolUrl(),
        ];
        $json['client_name'] = 'PuMuKIT LMS';
        $json['jwks_uri'] = $this->compoundToolUrl().PumukitLmsBundle::LTI_TOOL_PUBLIC_KEYSET_URL;
        $json['token_endpoint_auth_method'] = 'private_key_jwt';
        $json['https://purl.imsglobal.org/spec/lti-tool-configuration'] = [
            'domain' => $this->compoundToolUrlWithoutDomain(),
            'target_link_uri' => $this->compoundToolUrl().PumukitLmsBundle::LTI_TOOL_LAUNCH_URL,
            'custom_parameters' => [
                'userID' => '$User.id',
                'username' => '$User.username',
                'person_email' => '$Person.email.primary',
                'person_fullname' => '$Person.name.full'
            ],
            'claims' => ['iss', 'sub', 'name', 'given_name', 'family_name', 'email'],
            'messages' => [
                [
                    'type' => 'LtiDeepLinkingRequest',
                    'target_link_uri' => $this->compoundToolUrl().PumukitLmsBundle::LTI_TOOL_LAUNCH_URL,
                    'label' => 'Add PuMuKIT resource',
                ],
            ],
            'description' => 'Manage your PuMuKIT resources from your LMS.',
        ];
        $json['scope'] = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly https://purl.imsglobal.org/spec/lti-ags/scope/score https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';
        $json['logo_uri'] = $this->compoundToolUrl().PumukitLmsBundle::LTI_TOOL_FAVICON_URL;

        return json_encode($json);
    }
}
