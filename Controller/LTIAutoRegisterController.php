<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Pumukit\LmsBundle\Services\ConfigurationService;
use Pumukit\LmsBundle\Services\LTIRegister;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class LTIAutoRegisterController extends AbstractController
{
    private $configurationService;
    private $ltiRegister;

    public function __construct(ConfigurationService $configurationService, LTIRegister $ltiRegister)
    {
        $this->configurationService = $configurationService;
        $this->ltiRegister = $ltiRegister;
    }

    #[Route('/lti/register', name: 'lti_tool_register', methods: ['POST', 'GET'])]
    public function register(Request $request): Response
    {
        $params = $request->query->all();

        if (!is_string($params['openid_configuration'] ?? null)) {
            throw new BadRequestHttpException('Missing openid_configuration required parameter.');
        }

        if (str_contains($params['openid_configuration'], 'blackboard')) {
            $blackboardParams = (string) parse_url($params['openid_configuration'], PHP_URL_QUERY);
            $explode = explode('registrationToken=', $blackboardParams);
            $params['registration_token'] = $explode[1] ?? null;
        }

        if (!is_string($params['registration_token'] ?? null)) {
            throw new BadRequestHttpException('Missing registration_token required parameter.');
        }

        if (!$this->configurationService->isAllowedLmsUrl($params['openid_configuration'])) {
            throw new AccessDeniedHttpException('Invalid openid_configuration.');
        }

        try {
            $openIdConfiguration = $this->ltiRegister->openIdRequestConfiguration($params['openid_configuration']);
        } catch (\Exception $exception) {
            throw new BadRequestHttpException('The openid_configuration cannot be read.', $exception);
        }

        if (!$this->configurationService->isAllowedDomain($openIdConfiguration['issuer'])) {
            throw new AccessDeniedHttpException('Invalid issuer.');
        }

        if (!$this->configurationService->isAllowedLmsUrl($openIdConfiguration['registration_endpoint'])) {
            throw new AccessDeniedHttpException('Invalid registration_endpoint.');
        }

        try {
            $registration = $this->ltiRegister->register($openIdConfiguration['registration_endpoint'], $params['registration_token']);
        } catch (\Exception $exception) {
            throw new BadRequestHttpException('The registration failed.', $exception);
        }
        $this->ltiRegister->createRegistrationEntry($params, $openIdConfiguration, $registration);

        return $this->render('@PumukitLms/LTI/registration_response.html.twig');
    }
}
