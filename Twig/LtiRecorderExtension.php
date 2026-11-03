<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Twig;

use Pumukit\LmsBundle\Controller\LTIRecorderController;
use Pumukit\LmsBundle\Services\SignedToken;
use Pumukit\PersonalRecorderBundle\PumukitPersonalRecorderBundle;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LtiRecorderExtension extends AbstractExtension
{
    public function __construct(
        private readonly SignedToken $signedToken,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('lms_lti_recorder_url', [$this, 'recorderUrl']),
        ];
    }

    public function recorderUrl(): ?string
    {
        $request = $this->requestStack->getMainRequest();
        $user = $this->security->getUser();
        if (!class_exists(PumukitPersonalRecorderBundle::class) || !$user instanceof User || !$request?->hasPreviousSession() || !$request->getSession()->has('lti_client_id')) {
            return null;
        }

        $token = $this->signedToken->issue(LTIRecorderController::TOKEN, ['user' => (string) $user->getId()], LTIRecorderController::TTL);

        return $this->urlGenerator->generate('pumukit_lms_lti_recorder', ['token' => $token]);
    }
}
