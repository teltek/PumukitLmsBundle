<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MultimediaObjectVoter extends Voter
{
    public const PLAY = 'play';

    private $configurationService;
    private $mmobjService;
    private $requestStack;

    public function __construct(
        ConfigurationService $configurationService,
        MultimediaObjectService $mmobjService,
        RequestStack $requestStack
    ) {
        $this->configurationService = $configurationService;
        $this->mmobjService = $mmobjService;
        $this->requestStack = $requestStack;
    }

    protected function supports($attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (self::PLAY !== $attribute) {
            return false;
        }

        // only vote on Post objects inside this voter
        if (!$subject instanceof MultimediaObject) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $multimediaObject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (self::PLAY === $attribute) {
            return $this->canPlay($multimediaObject, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    protected function canPlay($multimediaObject, $user = null): bool
    {
        $req = $this->requestStack->getMasterRequest();

        if (!$this->mmobjService->isHidden($multimediaObject, 'PUCHLMS')) {
            return false;
        }

        $refererUrl = $req->headers->get('referer');

        if (!$refererUrl) {
            return false;
        }

        $refererQuery = parse_url($refererUrl, PHP_URL_QUERY);
        if (!$refererQuery) {
            return false;
        }

        parse_str($refererQuery, $query);
        if (!isset($query['hash'])) {
            return false;
        }

        $hash = $query['hash'];
        //Check TTK-16603 use multimediaObject.id
        if (!$this->configurationService->isValidHash($hash, '')) {
            return false;
        }

        return true;
    }
}
