<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use ceLTIc\LTI\DataConnector\DataConnector;
use ceLTIc\LTI\Tool;
use Pumukit\LmsBundle\Document\Consumer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ODM\MongoDB\DocumentManager as DocumentManager;

class LTIController extends AbstractController
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @Route("/lti/launch", name="lti_launch", methods={"POST"})
     */
    public function launch(Request $request): Response
    {
        $params = $request->request->all();
        $consumer_key = $params['oauth_consumer_key'];

        $consumer = $this->documentManager->getRepository(Consumer::class)->findOneBy(['consumer_key' => $consumer_key]);
        if (!$consumer) {
            throw $this->createNotFoundException('No consumer found for key ' . $consumer_key);
        }

        $data_connector = DataConnector::getDataConnector($consumer->getDsn(), $consumer->getDbName(), 'pdo');
        $toolProvider = new Tool($data_connector);
        $toolProvider->setParameterConstraint('resource_link_id', TRUE, 50);

        if(!$toolProvider->ok) {
            throw new \Exception("LTI request validation failed.");
        }

        $userId = $toolProvider->user->getId();
        $user = $this->documentManager->getRepository(User::class)->findOneBy(['userId' => $userId]);
        if (!$user) {
            $user = $this->createUser($toolProvider);
        }

        return $this->render('@PumukitLms/lti/launch.html.twig', [
            'username' => $user->getFullName(),
            'role'=> $user->getRoles(),
            'courseName' => $toolProvider->resource_link->settings['context_title']
        ]);

    }

    private function createUser(Tool $tool): User
    {
        $user = new User();
        $user->setRoles($tool->user->roles);
        $user->setFullName($tool->user->fullname);
        $user->setEmail($tool->user->email);
        $this->documentManager->persist($user);
        $this->documentManager->flush();

        return $user;
    }
}
