<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Pumukit\LmsBundle\Services\SSOService;
use Pumukit\LmsBundle\Utils\RequestParameter;
use Pumukit\SchemaBundle\Services\PersonalSeriesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/openedx/sso')]
class UploadController extends AbstractController
{
    private $SSOService;
    private $personalSeriesService;
    private $defaultUploadProfile;

    public function __construct(
        SSOService $SSOService,
        PersonalSeriesService $personalSeriesService,
        string $defaultUploadProfile
    ) {
        $this->SSOService = $SSOService;
        $this->personalSeriesService = $personalSeriesService;
        $this->defaultUploadProfile = $defaultUploadProfile;
    }

    #[Route('/upload', name: 'pumukit_lms_sso_upload')]
    public function upload(Request $request)
    {
        $email = RequestParameter::getString($request, 'email');
        $username = RequestParameter::getString($request, 'username');
        $hash = RequestParameter::getString($request, 'hash');

        if (!$this->isGranted('ROLE_SCOPE_GLOBAL') && !$this->isGranted('ROLE_SCOPE_PERSONAL')) {
            $user = $this->SSOService->getAndValidateUser(
                $email,
                $username,
                $request->headers->get('referer'),
                $hash,
                $request->isSecure()
            );
            if ($user instanceof Response) {
                return $user;
            }

            $this->SSOService->login($user, $request);
        }

        $series = $this->personalSeriesService->getOrCreate($this->getUser())->getId();

        $request->getSession()->set('tus_sso_email', $email);
        $request->getSession()->set('tus_sso_username', $username);

        $redirectUrl = $this->generateUrl('wizard_upload', ['series' => $series, 'show_profiles' => false, 'profile' => $this->defaultUploadProfile]);

        return new RedirectResponse($redirectUrl);
    }
}
