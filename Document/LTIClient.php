<?php

namespace Pumukit\LmsBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\LmsBundle\Repository\LTIClientRepository")
 */
class LTIClient
{
    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="string")
     */
    private $openid_configuration;

    /**
     * @MongoDB\Field(type="string")
     */
    private $registration_token;

    /**
     * @MongoDB\Field(type="string")
     */
    private $issuer;

    /**
     * @MongoDB\Field(type="string")
     */
    private $token_endpoint;

    /**
     * @MongoDB\Field(type="string")
     */
    private $authorization_endpoint;

    /**
     * @MongoDB\Field(type="string")
     */
    private $registration_endpoint;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $openIdRaw;

    /**
     * @MongoDB\Field(type="string")
     */
    private $client_id;

    /**
     * @MongoDB\Field(type="string")
     */
    private $lti_deployment_id;

    /**
     * @MongoDB\Field(type="raw")
     */
    private $registerRaw;

    public function __construct(array $ltiData)
    {
        $this->openid_configuration = $ltiData['openid_configuration'];
        $this->registration_token = $ltiData['registration_token'];
        $this->issuer = $ltiData['issuer'];
        $this->token_endpoint = $ltiData['token_endpoint'];
        $this->authorization_endpoint = $ltiData['authorization_endpoint'];
        $this->registration_endpoint = $ltiData['registration_endpoint'];
        $this->openIdRaw = $ltiData['openIdRaw'];
        $this->client_id = $ltiData['client_id'];
        $this->lti_deployment_id = $ltiData['lti_deployment_id'];
        $this->registerRaw = $ltiData['registerRaw'];
    }

    public static function create(array $ltiData): self
    {
        return new self($ltiData);
    }

    public function id()
    {
        return $this->id;
    }

    public function authorizationEndpoint(): string
    {
        return $this->authorization_endpoint;
    }

    public function clientId(): string
    {
        return $this->client_id;
    }

    public function issuer(): string
    {
        return $this->issuer;
    }

    public function targetLinkUri(): string
    {
        $registerRaw = json_decode($this->registerRaw, true);

        return $registerRaw['https://purl.imsglobal.org/spec/lti-tool-configuration']['target_link_uri'];
    }

    public function initiateLoginUri(): string
    {
        $registerRaw = json_decode($this->registerRaw, true);

        return $registerRaw['initiate_login_uri'];
    }

    public function getJWKSUri(): string
    {
        $openIdRaw = json_decode($this->openIdRaw, true);

        return $openIdRaw['jwks_uri'];
    }

    public function ltiDeploymentId(): ?string
    {
        return $this->lti_deployment_id;
    }
}
