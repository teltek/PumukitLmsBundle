<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Pumukit\LmsBundle\Services\ConfigurationService;
use Pumukit\LmsBundle\Services\LTIRegister;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class LTIAutoRegisterController extends AbstractController
{
    private $configurationService;
    private $ltiRegister;

    public function __construct(ConfigurationService $configurationService, LTIRegister $ltiRegister)
    {
        $this->configurationService = $configurationService;
        $this->ltiRegister = $ltiRegister;
    }

    /**
     * @Route("/lti/register", name="lti_tool_register", methods={"POST","GET"})
     */
    public function register(Request $request): Response
    {
        $params = $request->query->all();

        if (!isset($params['openid_configuration'])) {
            throw new \Exception('Missing openid_configuration required parameter.');
        }

        if (str_contains($params['openid_configuration'], 'blackboard')) {
            $blackboardParams = parse_url($params['openid_configuration'], PHP_URL_QUERY);
            $explode = explode('registrationToken=', $blackboardParams);
            $params['registration_token'] = $explode[1];
        }

        if (!isset($params['registration_token'])) {
            throw new \Exception('Missing registration_token required parameter.');
        }

        $openIdConfiguration = $this->ltiRegister->openIdRequestConfiguration($params['openid_configuration']);
        if (!$this->configurationService->isAllowedDomain($openIdConfiguration['issuer'])) {
            throw new \Exception('Invalid issuer.');
        }

        $registration = $this->ltiRegister->register($openIdConfiguration['registration_endpoint'], $params['registration_token']);
        $this->ltiRegister->createRegistrationEntry($params, $openIdConfiguration, $registration);

        return $this->render('@PumukitLms/LTI/registration_response.html.twig');
    }
}
