<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\LmsBundle\Services\SSOService;
use Pumukit\LmsBundle\Utils\SeriesUtils;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/sso")
 */
class UploadController extends AbstractController
{
    public const ADMIN_UPLOAD_ROUTE = '/admin/simplewizard/embedindex';
    private $documentManager;
    private $SSOService;
    private $uploadSeriesTitle;
    private $locales;

    public function __construct(
        DocumentManager $documentManager,
        SSOService $SSOService,
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

    private function getSeries($i18nTitle)
    {
        return $this->documentManager->getRepository(Series::class)->findOneBy(['title' => $i18nTitle]);
    }

    private function generateRouteParams(): string
    {
        $i18nTitle = SeriesUtils::buildI18nTitle($this->uploadSeriesTitle, $this->locales);
        $series = $this->getSeries($i18nTitle);
        if ($series) {
            $params = '?'.SeriesUtils::buildParams($i18nTitle, $series->getId());
        } else {
            $params = '?'.SeriesUtils::buildParams($i18nTitle, null);
        }

        return $params;
    }
}
