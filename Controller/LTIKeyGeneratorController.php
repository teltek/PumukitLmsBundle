<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Controller;

use Jose\Component\KeyManagement\JWKFactory;
use Pumukit\LmsBundle\Services\LTIKeyConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
    public function jwks(): Response
    {
        $key = JWKFactory::createFromKeyFile(
            $this->LTIKeyConfiguration->privateKey(),
            null,
            [
                'use' => 'sig',
                'alg' => 'RS256',
            ]
        );

        $kid = $key->thumbprint('sha256');

        $jwks = [
            'keys' => [
                'kty' => $key->get('kty'),
                'alg' => $key->get('alg'),
                'use' => $key->get('use'),
                'kid' => $kid,
                'n' => $key->get('n'),
                'e' => $key->get('e'),
            ],
        ];

        return new Response(json_encode($jwks));
    }
}
