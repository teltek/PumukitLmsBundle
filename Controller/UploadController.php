<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Pumukit\LmsBundle\Services\SeriesService;
use Pumukit\LmsBundle\Services\SSOService;
use Pumukit\LmsBundle\Utils\SeriesUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/sso")
 */
class UploadController extends AbstractController
{
    public const ADMIN_UPLOAD_ROUTE = '/admin/simplewizard/embedindex';
    private $SSOService;
    private $seriesService;

    public function __construct(
        SSOService $SSOService,
        SeriesService $seriesService
    ) {
        $this->SSOService = $SSOService;
        $this->seriesService = $seriesService;
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

        $series = $this->seriesService->getSeriesToUpload();
        $params = '?'.SeriesUtils::buildParams($this->seriesService->getDefaultSeriesTitle(), $series->getId());

        return new RedirectResponse(self::ADMIN_UPLOAD_ROUTE.$params);
    }
}
