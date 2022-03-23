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
    public const ADMIN_PERSONAL_RECORDER_ROUTE = '/admin/personalrecorder';

    /**
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

        $seriesService = $this->get('pumukit_lms.series_service');

        $series = $seriesService->getSeriesToUpload();
        $params = '?'.$this->buildParams($seriesService->getDefaultSeriesTitle(), $series->getId());
        $params .= '&showButton=false';

        return new RedirectResponse(self::ADMIN_PERSONAL_RECORDER_ROUTE.'/'.$params);
    }
}
