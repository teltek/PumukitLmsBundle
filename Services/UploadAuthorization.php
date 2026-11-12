<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;

final class UploadAuthorization
{
    private const TTL = 3600;
    private const ID = '/^[a-f\d]{24}$/i';

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly MultimediaObjectService $multimediaObjectService,
        private readonly string $secret,
    ) {}

    public function sign(User $user): array
    {
        $userId = (string) $user->getId();
        $expires = time() + self::TTL;

        return [$userId, $expires, $this->signature($userId, (string) $expires)];
    }

    public function verify(string $userId, string $expires, string $signature): ?User
    {
        $expiration = (int) $expires;
        if ($expiration <= time() || $expiration > time() + self::TTL || !preg_match(self::ID, $userId)) {
            return null;
        }
        if (!hash_equals($this->signature($userId, $expires), $signature)) {
            return null;
        }

        $user = $this->documentManager->getRepository(User::class)->find(new ObjectId($userId));

        return $user instanceof User ? $user : null;
    }

    public function canUploadTo(User $user, string $seriesId): bool
    {
        if (!preg_match(self::ID, $seriesId)) {
            return false;
        }

        $series = $this->documentManager->getRepository(Series::class)->find(new ObjectId($seriesId));
        if (!$series instanceof Series) {
            return false;
        }

        if (in_array(PermissionProfile::SCOPE_GLOBAL, $user->getRoles(), true)
            || (string) $user->getPersonalSeries() === $seriesId
        ) {
            return true;
        }

        $prototype = $this->documentManager->getRepository(MultimediaObject::class)->findPrototype($series);

        return $prototype instanceof MultimediaObject && $this->multimediaObjectService->isUserOwner($user, $prototype);
    }

    private function signature(string $userId, string $expires): string
    {
        return hash_hmac('sha256', 'lti_upload:'.$userId.':'.$expires, $this->secret);
    }
}
