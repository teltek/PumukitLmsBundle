<?php

namespace Pumukit\LmsBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/sso")
 */
class PersonalRecorderController extends SSOController
{
    const ADMIN_PERSONAL_RECORDER_ROUTE = '/admin/personalrecorder';

    /**
     * Parameters:
     *   - email or username
     *   - hash.
     *
     * @param Request $request
     *
     * @return null|RedirectResponse|Response
     *
     * @Route("/personal_recorder", name="pumukit_lms_sso_personalrecorder")
     */
    public function personalRecorder(Request $request)
    {
        $activeBundles = $this->container->getParameter('kernel.bundles');
        if (!array_key_exists('PumukitPersonalRecorderBundle', $activeBundles)) {
            return new Response($this->renderView('PumukitLmsBundle:PersonalRecorder:not_found.html.twig'), 403);
        }

        if (!$this->isGranted('ROLE_SCOPE_GLOBAL') && !$this->isGranted('ROLE_SCOPE_PERSONAL')) {
            $user = $this->getAndValidateUser($request->get('email'), $request->get('username'), $request->headers->get('referer'), $request->get('hash'), $request->isSecure());
            if ($user instanceof Response) {
                return $user;
            }
            $this->login($user, $request);
        }

        $titleParam = $this->getParameter('pumukit_lms.recording_series_title');
        $locales = $this->getParameter('pumukit2.locales');
        $i18nTitle = $this->buildI18nTitle($titleParam, $locales);
        $series = $this->getSeries($i18nTitle);
        if ($series) {
            $params = '?'.$this->buildParams($i18nTitle, $series->getId());
        } else {
            $params = '?'.$this->buildParams($i18nTitle, null);
        }
        $params .= '&showButton=false';

        return new RedirectResponse(self::ADMIN_PERSONAL_RECORDER_ROUTE.'/'.$params);
    }
}
