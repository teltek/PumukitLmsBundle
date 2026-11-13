<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;

class LTIKeyConfiguration
{
    public function __construct(private readonly string $ltiPrivateKey) {}

    public function hasKeys(): bool
    {
        return is_file($this->ltiPrivateKey);
    }

    public function privateKeyContent(): string
    {
        return file_get_contents($this->ltiPrivateKey);
    }

    public function kidFromJwkKey(): string
    {
        $key = $this->createJwkKey();

        return $key->thumbprint('sha256');
    }

    public function jwksFromJwkKey(): array
    {
        $key = $this->createJwkKey();
        $kid = $this->kidFromJwkKey();

        return [
            'keys' => [
                [
                    'kty' => $key->get('kty'),
                    'alg' => $key->get('alg'),
                    'use' => $key->get('use'),
                    'kid' => $kid,
                    'n' => $key->get('n'),
                    'e' => $key->get('e'),
                ],
            ],
        ];
    }

    private function createJwkKey(): JWK
    {
        return JWKFactory::createFromKeyFile(
            $this->ltiPrivateKey,
            null,
            [
                'use' => 'sig',
                'alg' => 'RS256',
            ]
        );
    }
}
