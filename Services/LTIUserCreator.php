<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\LDAPBundle\Services\LDAPService;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class LTIUserCreator extends SSOService
{
    private $documentManager;

    public function __construct(
        DocumentManager $documentManager,
        PermissionProfileService $permissionProfileService,
        UserService $userService,
        PersonService $personService,
        GroupService $groupService,
        ConfigurationService $configurationService,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $dispatcher,
        Environment $templating,
        bool $checkLDAPInfoToUpdatePermissionProfile,
        LDAPService $ldapService = null,
        RequestStack $requestStack = null,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $documentManager,
            $permissionProfileService,
            $userService,
            $personService,
            $groupService,
            $configurationService,
            $tokenStorage,
            $dispatcher,
            $templating,
            $checkLDAPInfoToUpdatePermissionProfile,
            $ldapService,
            $requestStack,
            $logger
        );
        $this->documentManager = $documentManager;
    }

    public function createUserFromResponse(string $userId, string $username, string $email, string $fullname, array $roles): User
    {
        $user = $this->searchUser($username, $email);

        if (!$user instanceof User) {
            return $this->createUserByUsernameAndEmail($username, $email, $fullname);
        }

        $this->promoteUser($user);

        return $user;
    }

    private function searchUser(string $username, string $email): ?User
    {
        $user = $this->documentManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$user instanceof User) {
            $user = $this->documentManager->getRepository(User::class)->findOneBy(['email' => $email]);
        }

        return $user;
    }
}
