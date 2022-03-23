<?php

namespace Pumukit\LmsBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/sso")
 */
class UploadController extends SSOController
{
    public const ADMIN_UPLOAD_ROUTE = '/admin/simplewizard/embedindex';

    /**
     * @Route("/upload", name="pumukit_lms_sso_upload")
     */
    public function upload(Request $request)
    {
        if (!$this->isGranted('ROLE_SCOPE_GLOBAL') && !$this->isGranted('ROLE_SCOPE_PERSONAL')) {
            $user = $this->getAndValidateUser($request->get('email'), $request->get('username'), $request->headers->get('referer'), $request->get('hash'), $request->isSecure());
            if ($user instanceof Response) {
                return $user;
            }

            $this->login($user, $request);
        }

        $seriesService = $this->get('pumukit_lms.series_service');
        $series = $seriesService->getSeriesToUpload();
        $params = '?'.$this->buildParams($seriesService->getDefaultSeriesTitle(), $series->getId());

        return new RedirectResponse(self::ADMIN_UPLOAD_ROUTE.$params);
    }
}
