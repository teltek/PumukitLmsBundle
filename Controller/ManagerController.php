<?php

namespace Pumukit\LmsBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/sso")
 */
class ManagerController extends SSOController
{
    const ADMIN_SERIES_ROUTE = '/admin/series';
    const ADMIN_PLAYLIST_ROUTE = '/admin/playlist';

    /**
     *   Parameters:
     *   - email or username
     *   - hash.
     *
     * @param Request $request
     *
     * @return null|RedirectResponse|Response
     *
     * @Route("/manager", name="pumukit_lms_sso_manager")
     */
    public function manager(Request $request)
    {
        if (!$this->isGranted(PermissionProfile::SCOPE_PERSONAL) && !$this->isGranted(PermissionProfile::SCOPE_GLOBAL)) {
            $user = $this->getAndValidateUser($request->get('email'), $request->get('username'), $request->getHost(), $request->get('hash'), $request->isSecure());
            if ($user instanceof Response) {
                return $user;
            }

            $this->login($user, $request);
        }

        if ($request->get('playlist')) {
            return new RedirectResponse(self::ADMIN_PLAYLIST_ROUTE);
        }

        return new RedirectResponse(self::ADMIN_SERIES_ROUTE);
    }
}
