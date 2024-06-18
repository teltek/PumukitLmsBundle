<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Pumukit\LmsBundle\Document\LTIClient;
use Pumukit\LmsBundle\Services\LTIUserCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LTILaunchController extends AbstractController
{
    public const ADMIN_SERIES_ROUTE = 'pumukitnewadmin_series_index';
    private $documentManager;
    private $client;
    private $ltiUserCreator;

    public function __construct(DocumentManager $documentManager, HttpClientInterface $httpClient, LTIUserCreator $ltiUserCreator)
    {
        $this->documentManager = $documentManager;
        $this->client = $httpClient;
        $this->ltiUserCreator = $ltiUserCreator;
    }

    /**
     * @Route("/lti/launch", name="lti_tool_launch", methods={"GET","POST"})
     */
    public function launch(Request $request): Response
    {
        $token = $request->request->get('id_token');
        $state = $request->request->get('state');
        $origin = $request->headers->get('origin');

        $client = $this->documentManager->getRepository(LTIClient::class)->findOneBy(['issuer' => $origin]);
        if (!$client) {
            throw new \Exception('Client not found.');
        }

        $jwksUri = $client->getJWKSUri();
        $response = $this->client->request('GET', $jwksUri, ['verify_peer' => false]);

        $jwks = json_decode($response->getContent(), true);

        $keys = JWK::parseKeySet($jwks);
        $decodedToken = JWT::decode($token, $keys);

        $userID = $decodedToken->{'https://purl.imsglobal.org/spec/lti/claim/custom'}->userID;
        $username = $decodedToken->{'https://purl.imsglobal.org/spec/lti/claim/custom'}->username;
        $mail = $decodedToken->{'https://purl.imsglobal.org/spec/lti/claim/custom'}->person_email;
        $fullname = $decodedToken->{'https://purl.imsglobal.org/spec/lti/claim/custom'}->person_fullname;
        $roles = $decodedToken->{'https://purl.imsglobal.org/spec/lti/claim/roles'};

        $user = $this->ltiUserCreator->createUserFromResponse($userID, $username, $mail, $fullname, $roles);

        $this->ltiUserCreator->login($user, $request);
        $session = $request->getSession();
        $session->set('lti_client_id', $client->id());
        $session->set('lti_deep_link_return_url', $decodedToken->{'https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings'}->deep_link_return_url);

        return $this->redirectToRoute(self::ADMIN_SERIES_ROUTE);
    }
}
