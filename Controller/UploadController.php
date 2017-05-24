<?php

namespace Pumukit\OpenEdxBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/sso")
 */
class UploadController extends SSOController
{
    const ADMIN_UPLOAD_ROUTE = '/admin/simplewizard/embedindex';

    /**
     * Parameters:
     *   - email or username
     *   - hash.
     *
     * @Route("/upload", name="pumukit_openedx_sso_upload")
     */
    public function upload(Request $request)
    {
        $user = $this->getAndValidateUser($request->get('email'), $request->get('username'), $request->getHost(), $request->get('hash'), $request->isSecure());
        if ($user instanceof Response) {
            return $user;
        }

        $this->login($user, $request);

        $titleParam = $this->getParameter('pumukit_openedx.upload_series_title');
        $locales = $this->getParameter('pumukit2.locales');
        $i18nTitle = $this->buildI18nTitle($titleParam, $locales);
        $series = $this->getSeries($i18nTitle);
        if ($series) {
            $params = '?'.$this->buildParams($i18nTitle, $series->getId());
        } else {
            $params = '?'.$this->buildParams($i18nTitle, null);
        }

        return new RedirectResponse(self::ADMIN_UPLOAD_ROUTE.$params);
    }
}
