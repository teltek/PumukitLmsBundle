<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Pumukit\LmsBundle\Services\ConfigurationService;
use Pumukit\LmsBundle\Services\SeriesService;
use Pumukit\LmsBundle\Services\SSOService;
use Pumukit\LmsBundle\Utils\SeriesUtils;
use Pumukit\PersonalRecorderBundle\PumukitPersonalRecorderBundle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/openedx/sso")
 */
class PersonalRecorderController extends AbstractController
{
    public const ADMIN_PERSONAL_RECORDER_ROUTE = '/admin/personalrecorder';

    private $SSOService;
    private $seriesService;
    private $configurationService;

    public function __construct(
        SSOService $SSOService,
        SeriesService $seriesService,
        ConfigurationService $configurationService,
    ) {
        $this->SSOService = $SSOService;
        $this->seriesService = $seriesService;
        $this->configurationService = $configurationService;
    }

    /**
     * @Route("/personal_recorder", name="pumukit_lms_sso_personalrecorder")
     */
    public function personalRecorder(Request $request)
    {
        if (!class_exists(PumukitPersonalRecorderBundle::class)) {
            return new Response($this->renderView('@PumukitLms/PersonalRecorder/not_found.html.twig'), 403);
        }

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

        $series = $this->seriesService->getSeriesToUpload();
        $params = '?'.SeriesUtils::buildParams($this->configurationService->getDefaultSeriesTitle(), $series->getId());
        $params .= '&showButton=false';

        return new RedirectResponse(self::ADMIN_PERSONAL_RECORDER_ROUTE.'/'.$params);
    }
}
