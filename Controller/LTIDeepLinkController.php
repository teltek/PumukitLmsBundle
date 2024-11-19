<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Firebase\JWT\JWT;
use MongoDB\BSON\ObjectId;
use Pumukit\LmsBundle\Document\LTIClient;
use Pumukit\LmsBundle\Services\ConfigurationService;
use Pumukit\LmsBundle\Services\LTIKeyConfiguration;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LTIDeepLinkController extends AbstractController
{
    private $documentManager;
    private $httpClient;
    private $configurationService;
    private $LTIKeyConfiguration;

    public function __construct(DocumentManager $documentManager, HttpClientInterface $httpClient, ConfigurationService $configurationService, LTIKeyConfiguration $LTIKeyConfiguration)
    {
        $this->documentManager = $documentManager;
        $this->httpClient = $httpClient;
        $this->configurationService = $configurationService;
        $this->LTIKeyConfiguration = $LTIKeyConfiguration;
    }

    /**
     * @Route("/lti/deep_link/{id}", name="lti_tool_deep_link", methods={"GET","POST"})
     */
    public function deepLink(Request $request, string $id)
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => $id]);
        if (!$multimediaObject) {
            throw new \Exception('Multimedia object not found.');
        }

        $session = $request->getSession();

        $ltiClient = $this->documentManager->getRepository(LTIClient::class)->findOneBy(['_id' => new ObjectId($session->get('lti_client_id'))]);
        if (!$ltiClient) {
            throw new \Exception('Client not valid.');
        }

        $ltiContent = $this->generateLTIContentLink($multimediaObject);

        $privateKey = $this->LTIKeyConfiguration->privateKeyContent();
        $deploymentId = $session->get('lti_deployment_id');

        $claims = [
            'iss' => $ltiClient->clientId(),
            'aud' => [$ltiClient->clientId()],
            'sub' => $ltiClient->clientId(),
            'iat' => time(),
            'exp' => time() + 3600,
            'nonce' => bin2hex(random_bytes(32)),
            'https://purl.imsglobal.org/spec/lti/claim/deployment_id' => $deploymentId,
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiDeepLinkingResponse',
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
            'https://purl.imsglobal.org/spec/lti-dl/claim/content_items' => [
                $ltiContent,
            ],
        ];

        $kid = $this->LTIKeyConfiguration->kidFromJwkKey();

        $jwt = JWT::encode($claims, $privateKey, 'RS256', $kid);

        return $this->render('@PumukitLms/LTI/deep_link_response.html.twig', [
            'id_token' => $jwt,
            'target_link_uri' => $session->get('lti_deep_link_return_url'),
        ]);
    }

    private function generateLTIContentLink(MultimediaObject $multimediaObject): array
    {
        return [
            'type' => 'ltiResourceLink',
            'url' => $this->generateUrl(
                'pumukit_lms_openedx_embed_3',
                [
                    'id' => $multimediaObject->getId(),
                    'hash' => $this->configurationService->generateHash(''),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'title' => $multimediaObject->getTitle(),
            'presentation' => [
                'documentTarget' => 'iframe',
            ],
        ];
    }
}
