<?php

namespace Pumukit\LmsBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\LmsBundle\Document\LTIClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LTILoginController extends AbstractController
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @Route("/lti/login", name="lti_tool_login", methods={"POST", "GET"})
     */
    public function login(Request $request)
    {
        $data = $request->request->all();

        $ltiClient = $this->documentManager->getRepository(LTIClient::class)->findOneBy(['client_id' => $data['client_id']]);
        if (!$ltiClient) {
            throw new \Exception('Client not found.');
        }

        $authParams = [
            'action' => $ltiClient->authorizationEndpoint(),
            'scope' => 'openid',
            'response_type' => 'id_token',
            'client_id' => $ltiClient->clientId(),
            'redirect_uri' => $ltiClient->targetLinkUri(),
            'login_hint' => $data['login_hint'],
            'state' => bin2hex(random_bytes(16)),
            'response_mode' => 'form_post',
            'nonce' => bin2hex(random_bytes(16)),
            'prompt' => 'none',
            'lti_message_hint' => $data['lti_message_hint'],
            'lti_deployment_id' => $ltiClient->ltiDeploymentId(),
        ];

        return $this->render('@PumukitLms/LTI/login_response.html.twig', $authParams);
    }
}
