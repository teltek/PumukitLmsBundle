<?php

namespace Pumukit\LmsBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/sso")
 */
class ManagerController extends SSOController
{
    public const ADMIN_SERIES_ROUTE = 'pumukitnewadmin_series_index';
    public const ADMIN_MULTIMEDIAOBJECT_ROUTE = 'pumukitnewadmin_mms_shortener';
    public const ADMIN_PLAYLIST_ROUTE = 'pumukitnewadmin_playlist_index';

    /**
     * @Route("/manager", name="pumukit_lms_sso_manager")
     */
    public function manager(Request $request)
    {
        $forceReLogin = false;

        $user = $this->getUser();
        if (!$user || $user->getEmail() !== $request->get('email') || $user->getUsername() !== $request->get('username')) {
            $forceReLogin = true;
        }

        if (!$this->isGranted('ROLE_SCOPE_GLOBAL') && !$this->isGranted('ROLE_SCOPE_PERSONAL')) {
            $forceReLogin = true;
        }

        if ($forceReLogin) {
            $user = $this->getAndValidateUser($request->get('email') ?? '', $request->get('username'), $request->headers->get('referer'), $request->get('hash'), $request->isSecure());

            if ($user instanceof Response) {
                return $user;
            }

            $this->login($user, $request);
        }

        if ($request->get('playlist')) {
            return $this->redirectToRoute(self::ADMIN_PLAYLIST_ROUTE);
        }

        if ($mmobjId = $request->get('multimediaObject')) {
            return $this->redirectToRoute(self::ADMIN_MULTIMEDIAOBJECT_ROUTE, ['id' => $mmobjId]);
        }

        return $this->redirectToRoute(self::ADMIN_SERIES_ROUTE);
    }
}
