<?php

namespace Pumukit\OpenEdxBundle\Controller;

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

    /**
     * Parameters:
     *   - email or username
     *   - hash.
     *
     * @Route("/manager", name="pumukit_openedx_sso_manager")
     */
    public function manager(Request $request)
    {
        $user = $this->getAndValidateUser($request->get('email'), $request->get('username'), $request->getHost(), $request->get('hash'), $request->isSecure());
        if ($user instanceof Response) {
            return $user;
        }

        $this->login($user, $request);

        return new RedirectResponse(self::ADMIN_SERIES_ROUTE);
    }
}
