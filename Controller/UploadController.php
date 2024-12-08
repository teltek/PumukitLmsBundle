<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Pumukit\LmsBundle\Services\SeriesService;
use Pumukit\LmsBundle\Services\SSOService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/openedx/sso")
 */
class UploadController extends AbstractController
{
    private $SSOService;
    private $seriesService;
    private $defaultUploadProfile;

    public function __construct(
        SSOService $SSOService,
        SeriesService $seriesService,
        string $defaultUploadProfile
    ) {
        $this->SSOService = $SSOService;
        $this->seriesService = $seriesService;
        $this->defaultUploadProfile = $defaultUploadProfile;
    }

    /**
     * @Route("/upload", name="pumukit_lms_sso_upload")
     */
    public function upload(Request $request)
    {
        if (!$this->isGranted('ROLE_SCOPE_GLOBAL') && !$this->isGranted('ROLE_SCOPE_PERSONAL')) {
            $user = $this->SSOService->getAndValidateUser(
                $request->get('email'),
                $request->get('username'),
                $request->headers->get('referer'),
                $request->get('hash'),
                $request->isSecure()
            );
            if ($user instanceof Response) {
                return $user;
            }

            $this->SSOService->login($user, $request);
        }

        $series = $this->getUser()->getPersonalSeries();
        if (!$series) {
            $series = $this->seriesService->getSeriesToUpload();
        }

        $redirectUrl = $this->generateUrl('wizard_upload', ['series' => $series->getId(), 'show_profiles' => false, 'profile' => $this->defaultUploadProfile]);

        return new RedirectResponse($redirectUrl);
    }
}
