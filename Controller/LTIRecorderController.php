<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\LmsBundle\Services\SignedToken;
use Pumukit\LmsBundle\Services\SSOService;
use Pumukit\LmsBundle\Utils\SeriesUtils;
use Pumukit\PersonalRecorderBundle\PumukitPersonalRecorderBundle;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LTIRecorderController extends AbstractController
{
    public const TOKEN = 'lti_recorder';
    public const TTL = 300;

    public function __construct(
        private readonly SignedToken $signedToken,
        private readonly DocumentManager $documentManager,
        private readonly SSOService $ssoService,
    ) {}

    #[Route('/lti/recorder/{token}', name: 'pumukit_lms_lti_recorder', methods: ['GET'])]
    public function recorder(Request $request, string $token): Response
    {
        if (!class_exists(PumukitPersonalRecorderBundle::class)) {
            return $this->render('@PumukitLms/PersonalRecorder/not_found.html.twig', [], new Response('', Response::HTTP_FORBIDDEN));
        }

        $data = $this->signedToken->consume(self::TOKEN, $token);
        $user = is_string($data['user'] ?? null) ? $this->documentManager->getRepository(User::class)->find(new ObjectId($data['user'])) : null;
        if (!$user instanceof User) {
            return $this->render('@PumukitLms/SSO/error.html.twig', ['message' => 'The link has expired. Open the recorder again from the LMS.'], new Response('', Response::HTTP_FORBIDDEN));
        }

        $this->ssoService->login($user, $request);

        return $this->redirect('/admin/personalrecorder/?'.SeriesUtils::buildParams().'&showButton=false');
    }
}
