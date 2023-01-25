<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
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
    private $documentManager;
    private $SSOService;
    private $uploadSeriesTitle;
    private $seriesService;
    private $locales;

    public function __construct(
        DocumentManager $documentManager,
        SSOService $SSOService,
        SeriesService $seriesService,
        string $uploadSeriesTitle,
        array $locales
    ) {
        $this->documentManager = $documentManager;
        $this->SSOService = $SSOService;
        $this->uploadSeriesTitle = $uploadSeriesTitle;
        $this->locales = $locales;
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

        return new RedirectResponse(self::ADMIN_UPLOAD_ROUTE.$this->generateRouteParams());
    }

    private function getSeries()
    {
        return $this->seriesService->getSeriesToUpload();
    }

    private function generateRouteParams(): string
    {
        $i18nTitle = SeriesUtils::buildI18nTitle($this->uploadSeriesTitle, $this->locales);
        $series = $this->getSeries();
        if ($series) {
            $params = '?'.SeriesUtils::buildParams($i18nTitle, $series->getId());
        } else {
            $params = '?'.SeriesUtils::buildParams($i18nTitle, null);
        }

        return $params;
    }
}
