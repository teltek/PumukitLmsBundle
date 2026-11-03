<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Pumukit\LmsBundle\Services\SSOService;
use Pumukit\LmsBundle\Utils\RequestParameter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/openedx/sso')]
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

    #[Route('/manager', name: 'pumukit_lms_sso_manager')]
    public function manager(Request $request)
    {
        $session = $request->getSession();

        $email = RequestParameter::get($request, 'email');
        $username = RequestParameter::get($request, 'username');
        $hash = RequestParameter::get($request, 'hash');

        if ($email || $username) {
            if (
                $session->get('tus_sso_email') !== $email
                || $session->get('tus_sso_username') !== $username
            ) {
                $session->set('tus_sso_email', $email);
                $session->set('tus_sso_username', $username);
            }
        }

        $forceReLogin = false;

        $user = $this->getUser();
        if (!$user || $user->getEmail() !== $email || $user->getUsername() !== $username) {
            $forceReLogin = true;
        }

        if (!$this->isGranted('ROLE_SCOPE_GLOBAL') && !$this->isGranted('ROLE_SCOPE_PERSONAL')) {
            $forceReLogin = true;
        }

        if ($forceReLogin) {
            $user = $this->SSOService->getAndValidateUser(
                $email ?? '',
                $username,
                $request->headers->get('referer'),
                $hash,
                $request->isSecure()
            );

            if ($user instanceof Response) {
                return $user;
            }

            $this->SSOService->login($user, $request);
        }

        if (RequestParameter::get($request, 'playlist')) {
            return $this->redirectToRoute(self::ADMIN_PLAYLIST_ROUTE);
        }

        if ($mmobjId = RequestParameter::get($request, 'multimediaObject')) {
            return $this->redirectToRoute(self::ADMIN_MULTIMEDIAOBJECT_ROUTE, ['id' => $mmobjId]);
        }

        return $this->redirectToRoute(self::ADMIN_SERIES_ROUTE, ['action' => 'reset_criteria']);
    }
}
