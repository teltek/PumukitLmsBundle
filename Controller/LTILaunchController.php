<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Psr\Log\LoggerInterface;
use Pumukit\LmsBundle\Services\ConfigurationService;
use Pumukit\LmsBundle\Services\LtiException;
use Pumukit\LmsBundle\Services\LtiLaunchValidator;
use Pumukit\LmsBundle\Services\LtiRoles;
use Pumukit\LmsBundle\Services\LTIUserCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LTILaunchController extends AbstractController
{
    public const ADMIN_SERIES_ROUTE = 'pumukitnewadmin_series_index';
    private const RESOURCE_LINK = 'LtiResourceLinkRequest';
    private const DEEP_LINKING = 'LtiDeepLinkingRequest';
    private const DEEP_LINKING_SETTINGS = 'https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings';

    public function __construct(
        private readonly LtiLaunchValidator $launchValidator,
        private readonly LTIUserCreator $ltiUserCreator,
        private readonly ConfigurationService $configurationService,
        private readonly LoggerInterface $logger,
        private readonly ?string $learnerProfile = null,
    ) {}

    #[Route('/lti/launch', name: 'lti_tool_launch', methods: ['GET', 'POST'])]
    public function launch(Request $request): Response
    {
        try {
            [$client, $claims] = $this->launchValidator->validate($request->request->getString('id_token'), $request->request->getString('state'));
        } catch (LtiException $exception) {
            $this->logger->warning('LTI launch rejected', ['reason' => $exception->getMessage()]);

            return $this->error($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
        }

        $messageType = $claims->{LtiLaunchValidator::CLAIM.'message_type'} ?? null;
        $privileged = LtiRoles::isPrivileged((array) ($claims->{LtiLaunchValidator::CLAIM.'roles'} ?? []));

        if (!$privileged && null === $this->learnerProfile) {
            if (self::RESOURCE_LINK === $messageType) {
                return $this->redirectToResource($request, (string) ($claims->{LtiLaunchValidator::CLAIM.'target_link_uri'} ?? ''));
            }

            return $this->error('Only the teachers can add videos', Response::HTTP_FORBIDDEN);
        }

        [$userId, $username, $email, $fullName] = $this->identity($claims);
        if ('' === $username || '' === $email) {
            return $this->error('The LTI launch has no username or email', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->ltiUserCreator->createUserFromResponse($userId, $username, $email, $fullName, $privileged ? null : $this->learnerProfile);
        $this->ltiUserCreator->login($user, $request);

        $session = $request->getSession();
        $session->set('lti_client_id', $client->id());
        $session->set('lti_upload_user_id', (string) $user->getId());
        $session->set('lti_iss', $claims->iss);
        $session->set('lti_sub', $claims->sub ?? '');

        if (self::RESOURCE_LINK === $messageType) {
            return $this->redirectToResource($request, (string) ($claims->{LtiLaunchValidator::CLAIM.'target_link_uri'} ?? ''));
        }

        if (self::DEEP_LINKING === $messageType) {
            if (isset($claims->{LtiLaunchValidator::CLAIM.'deployment_id'})) {
                $session->set('lti_deployment_id', $claims->{LtiLaunchValidator::CLAIM.'deployment_id'});
            }
            $settings = $claims->{self::DEEP_LINKING_SETTINGS} ?? null;
            if (is_object($settings)) {
                $session->set('lti_deep_link_return_data', $settings->data ?? $settings);
                $session->set('lti_deep_link_return_url', $settings->deep_link_return_url ?? null);
            }
        }

        return $this->redirectToRoute(self::ADMIN_SERIES_ROUTE);
    }

    private function identity(object $claims): array
    {
        $custom = (array) ($claims->{LtiLaunchValidator::CLAIM.'custom'} ?? []);
        $institutionalEmail = (string) ($custom['person_email_institutional'] ?? '');
        $email = '' !== $institutionalEmail && !str_contains($institutionalEmail, '@X@') ? $institutionalEmail : (string) ($custom['person_email'] ?? $claims->email ?? '');
        $username = (string) ($custom['username'] ?? '');

        return [
            (string) ($custom['userID'] ?? $claims->sub ?? ''),
            '' !== $username ? $username : $email,
            $email,
            (string) ($custom['person_fullname'] ?? $claims->name ?? $username),
        ];
    }

    private function redirectToResource(Request $request, string $target): Response
    {
        $parts = parse_url($target);
        if (false === $parts || (isset($parts['host']) && strtolower($parts['host']) !== strtolower($request->getHost()))) {
            return $this->error('Invalid target link', Response::HTTP_BAD_REQUEST);
        }

        parse_str($parts['query'] ?? '', $query);
        if (array_key_exists('hash', $query)) {
            $query['hash'] = $this->configurationService->generateHash('');
        }

        $url = ($parts['path'] ?? '/').([] === $query ? '' : '?'.http_build_query($query));

        return $this->redirect($url);
    }

    private function error(string $message, int $status): Response
    {
        return $this->render('@PumukitLms/SSO/error.html.twig', ['message' => $message], new Response('', $status));
    }
}
