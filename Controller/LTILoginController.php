<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\LmsBundle\Document\LTIClient;
use Pumukit\LmsBundle\Services\LtiLaunchValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LTILoginController extends AbstractController
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly LtiLaunchValidator $launchValidator,
    ) {}

    #[Route('/lti/login', name: 'lti_tool_login', methods: ['POST', 'GET'])]
    public function login(Request $request): Response
    {
        $data = $request->request->all() ?: $request->query->all();
        $clientId = is_string($data['client_id'] ?? null) ? $data['client_id'] : '';
        $loginHint = is_string($data['login_hint'] ?? null) ? $data['login_hint'] : '';
        if ('' === $clientId || '' === $loginHint) {
            return $this->error('Missing client_id or login_hint', Response::HTTP_BAD_REQUEST);
        }

        $ltiClient = $this->documentManager->getRepository(LTIClient::class)->findOneBy(['client_id' => $clientId]);
        if (!$ltiClient instanceof LTIClient || (isset($data['iss']) && $data['iss'] !== $ltiClient->issuer())) {
            return $this->error('Client not found', Response::HTTP_BAD_REQUEST);
        }

        $session = $request->getSession();
        $session->set('lti_deployment_id', $ltiClient->ltiDeploymentId() ?? ($data['lti_deployment_id'] ?? null));
        [$state, $nonce] = $this->launchValidator->state($ltiClient);

        return $this->render('@PumukitLms/LTI/login_response.html.twig', [
            'action' => $ltiClient->authorizationEndpoint(),
            'scope' => 'openid',
            'response_type' => 'id_token',
            'client_id' => $ltiClient->clientId(),
            'redirect_uri' => $ltiClient->targetLinkUri(),
            'login_hint' => $loginHint,
            'state' => $state,
            'response_mode' => 'form_post',
            'nonce' => $nonce,
            'prompt' => 'none',
            'lti_message_hint' => $data['lti_message_hint'] ?? '',
            'lti_deployment_id' => $session->get('lti_deployment_id'),
        ]);
    }

    private function error(string $message, int $status): Response
    {
        return $this->render('@PumukitLms/SSO/error.html.twig', ['message' => $message], new Response('', $status));
    }
}
