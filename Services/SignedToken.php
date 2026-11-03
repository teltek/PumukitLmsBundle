<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

use Firebase\JWT\JWT;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class SignedToken
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly string $secret,
    ) {}

    public function issue(string $purpose, array $data, int $ttl): string
    {
        $payload = JWT::urlsafeB64Encode(json_encode(['p' => $purpose, 'e' => time() + $ttl, 'n' => bin2hex(random_bytes(16)), 'd' => $data], JSON_THROW_ON_ERROR));

        return $payload.'.'.$this->signature($payload);
    }

    public function consume(string $purpose, string $token): ?array
    {
        $parts = explode('.', $token);
        if (2 !== count($parts) || !hash_equals($this->signature($parts[0]), $parts[1])) {
            return null;
        }

        $payload = json_decode((string) JWT::urlsafeB64Decode($parts[0]), true);
        if (!is_array($payload) || $purpose !== ($payload['p'] ?? null) || !is_int($payload['e'] ?? null) || $payload['e'] < time() || !is_array($payload['d'] ?? null)) {
            return null;
        }

        $first = false;
        $this->cache->get('pumukit_lms_token_'.hash('sha256', $token), static function (ItemInterface $item) use ($payload, &$first): bool {
            $item->expiresAt(new \DateTimeImmutable('@'.$payload['e']));
            $first = true;

            return true;
        });

        return $first ? $payload['d'] : null;
    }

    private function signature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }
}
