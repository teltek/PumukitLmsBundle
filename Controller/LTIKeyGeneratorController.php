<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Pumukit\LmsBundle\Services\LTIKeyConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class LTIKeyGeneratorController extends AbstractController
{
    private $LTIKeyConfiguration;

    public function __construct(LTIKeyConfiguration $LTIKeyConfiguration)
    {
        $this->LTIKeyConfiguration = $LTIKeyConfiguration;
    }

    /**
     * @Route(".well-known/lti/jwks.json", name="lti_tool_jwks", methods={"GET"})
     */
    public function jwks(): JsonResponse
    {
        return new JsonResponse($this->LTIKeyConfiguration->jwksFromJwkKey());
    }
}
