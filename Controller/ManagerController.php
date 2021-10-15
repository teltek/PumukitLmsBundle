<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Pumukit\LmsBundle\Services\SSOService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/sso")
 */
class ManagerController extends AbstractController
{
    public const ADMIN_SERIES_ROUTE = 'pumukitnewadmin_series_index';
    public const ADMIN_MULTIMEDIAOBJECT_ROUTE = 'pumukitnewadmin_mms_shortener';
    public const ADMIN_PLAYLIST_ROUTE = 'pumukitnewadmin_playlist_index';

    private $SSOService;

    public function __construct(SSOService $SSOService)
    {
        $this->SSOService = $SSOService;
    }

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
            $user = $this->SSOService->getAndValidateUser(
                $request->get('email') ?? '',
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

        if ($request->get('playlist')) {
            return $this->redirectToRoute(self::ADMIN_PLAYLIST_ROUTE);
        }

        if ($mmobjId = $request->get('multimediaObject')) {
            return $this->redirectToRoute(self::ADMIN_MULTIMEDIAOBJECT_ROUTE, ['id' => $mmobjId]);
        }

        return $this->redirectToRoute(self::ADMIN_SERIES_ROUTE);
    }
}
