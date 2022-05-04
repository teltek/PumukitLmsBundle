<?php

namespace Pumukit\LmsBundle\Services;

use Pumukit\LmsBundle\PumukitLmsBundle;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MultimediaObjectVoter extends Voter
{
    public const PLAY = 'play';

    private $mmobjService;
    private $requestStack;
    private $ssoService;

    public function __construct(MultimediaObjectService $mmobjService, RequestStack $requestStack, SSOService $ssoService)
    {
        $this->mmobjService = $mmobjService;
        $this->requestStack = $requestStack;
        $this->ssoService = $ssoService;
    }

    protected function supports($attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::PLAY])) {
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

        if (!$this->mmobjService->isHidden($multimediaObject, PumukitLmsBundle::LMS_TAG_CODE)) {
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
        if (!isset($query['playlistId']) && !isset($query['hash'])) {
            return false;
        }

        $hash = $query['hash'];
        //Check TTK-16603 use multimediaObject.id
        if (!isset($query['playlistId']) && !$this->ssoService->validateHash($hash, '')) {
            return false;
        }

        return true;
    }
}
