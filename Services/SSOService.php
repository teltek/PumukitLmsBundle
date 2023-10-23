<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\LDAPBundle\Services\LDAPService;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class SSOService
{
    public const USER_ORIGIN = 'openedx';
    public const GROUP_KEY = 'edupersonprimaryaffiliation';
    public const LDAP_PDI = 'PDI';
    public const LDAP_PAS = 'PAS';
    public const LDAP_ID_KEY = 'uid';
    public const PERMISSION_PROFILE_AUTO = 'Auto Publisher';
    public const PERMISSION_PROFILE_VIEWER = 'Viewer';
    public const GROUP_ORIGIN = 'cas';

    private $documentManager;
    private $permissionProfileService;
    private $userService;
    private $personService;
    private $groupService;
    private $configurationService;
    private $ldapService;
    private $requestStack;
    private $tokenStorage;
    private $templating;
    private $dispatcher;
    private $checkLDAPInfoToUpdatePermissionProfile;

    private $logger;

    public function __construct(
        DocumentManager $documentManager,
        PermissionProfileService $permissionProfileService,
        UserService $userService,
        PersonService $personService,
        GroupService $groupService,
        ConfigurationService $configurationService,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $dispatcher,
        \Twig\Environment $templating,
        bool $checkLDAPInfoToUpdatePermissionProfile,
        LDAPService $ldapService = null,
        RequestStack $requestStack = null,
        LoggerInterface $logger
    ) {
        $this->documentManager = $documentManager;
        $this->permissionProfileService = $permissionProfileService;
        $this->userService = $userService;
        $this->personService = $personService;
        $this->groupService = $groupService;
        $this->configurationService = $configurationService;
        $this->ldapService = $ldapService;
        $this->requestStack = $requestStack;
        $this->templating = $templating;
        $this->tokenStorage = $tokenStorage;
        $this->dispatcher = $dispatcher;
        $this->checkLDAPInfoToUpdatePermissionProfile = $checkLDAPInfoToUpdatePermissionProfile;
        $this->logger = $logger;
    }

    public function login(UserInterface $user, Request $request): void
    {
        $token = new UsernamePasswordToken($user, $user->getPassword(), 'public', $user->getRoles());
        $this->tokenStorage->setToken($token);
        $event = new InteractiveLoginEvent($request, $token);
        $this->dispatcher->dispatch($event, 'security.interactive_login');
    }

    public function createUser(array $info): User
    {
        try {
            $info = $this->getInfoFromLDAP($info);
        } catch (\Exception $exception) {
            throw new \Exception('LDAP Service not enabled');
        }

        if (!$info) {
            throw new \Exception('User not found on LDAP');
        }

        if (!isset($info[self::GROUP_KEY][0]) || !in_array($info[self::GROUP_KEY][0], [self::LDAP_PAS, self::LDAP_PDI])) {
            throw new \Exception('User data invalid.');
        }

        $user = $this->createUserWithInfo($info);
        $group = $this->getGroup($info[self::GROUP_KEY][0]);
        $this->userService->addGroup($group, $user, true, false);

        $this->personService->referencePersonIntoUser($user);

        return $user;
    }

    public function promoteUser(User $user): void
    {
        $updateUser = false;
        $permissionProfileViewer = $this->permissionProfileService->getByName(self::PERMISSION_PROFILE_VIEWER);
        $permissionProfileAutoPub = $this->permissionProfileService->getByName(self::PERMISSION_PROFILE_AUTO);

        try {
            $info = $this->getInfoFromLDAP(['email' => $user->getEmail()]);
        } catch (\Exception $exception) {
            return;
        }

        if ($permissionProfileViewer == $user->getPermissionProfile()) {
            if ($this->checkLDAPInfoToUpdatePermissionProfile && (!isset($info[self::GROUP_KEY][0])
                || !in_array($info[self::GROUP_KEY][0], [self::LDAP_PAS, self::LDAP_PDI]))) {
                throw new \RuntimeException('User invalid.');
            }

            $user->setPermissionProfile($permissionProfileAutoPub);
            $updateUser = true;
        }

        if (isset($info['cn'][0])) {
            $user->setFullname($info['cn'][0]);
            $updateUser = true;
        }

        if ($updateUser) {
            $this->userService->update($user, true, false);
        }
    }

    public function createUserWithInfo(array $info): User
    {
        $username = $info[self::LDAP_ID_KEY][0];
        $email = $info['mail'][0];
        $fullName = $info['cn'][0];

        return $this->createUserByUsernameAndEmail($username, $email, $fullName);
    }

    public function createUserByUsernameAndEmail($username, $email, $fullName): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $info = $this->getInfoFromLDAP(['email' => $email, 'username' => $username]);
        if (!empty($info)) {
            $user->setFullname($info['cn'][0]);
        } else {
            $user->setFullname($fullName);
        }

        $permissionProfile = $this->permissionProfileService->getByName(self::PERMISSION_PROFILE_AUTO);
        $user->setPermissionProfile($permissionProfile);
        $user->setOrigin(self::USER_ORIGIN);
        $user->setEnabled(true);

        if ($this->requestStack && ($req = $this->requestStack->getMasterRequest())) {
            $user->setProperty('lms_origin', $req->headers->get('referer'));
        }

        $this->userService->create($user);
        $this->personService->referencePersonIntoUser($user);

        return $user;
    }

    public function getAndValidateUser(string $email, string $username, string $host, string $hash, bool $isSecure)
    {
        if (!$this->configurationService->getNakedBackofficeDomain()) {
            return $this->genError('The domain "pumukit.naked_backoffice_domain" is not configured.');
        }

        if ($username) {
            $type = 'username';
            $value = $username;
        } elseif (!empty($email)) {
            $type = 'email';
            $value = $email;
        } else {
            return $this->genError('Not email or username parameter.');
        }

        if (!$this->configurationService->isAllowedDomain($host)) {
            return $this->genError('Invalid Domain!');
        }

        if (!$this->configurationService->isValidHash($hash, $value)) {
            return $this->genError('The hash is not valid.');
        }

        if (!$isSecure) {
            return $this->genError('Only HTTPS connections are allowed.');
        }

        $repo = $this->documentManager->getRepository(User::class);

        // Find User
        try {
            $user = null;
            if ($username) {
                $user = $repo->findOneBy(['username' => $username]);
            }
            if (!$user && $email) {
                $user = $repo->findOneBy(['email' => $email]);
            }
            if (!$user) {
                $user = $this->createUser([$type => $value]);
            } else {
                $this->promoteUser($user);
            }
        } catch (\RuntimeException $e) {
            $this->logger->info('TTK Runtime exception');
        } catch (\Exception $e) {
            if ($this->configurationService->isAllowCreateUsersFromRequest() && $email && $username) {
                return $this->createUserByUsernameAndEmail($username, $email, $username);
            }

            return $this->genError($e->getMessage());
        }

        return $user;
    }

    protected function genError(string $message = 'Not Found', int $status = 404): Response
    {
        return new Response(
            $this->templating->render('@PumukitLms/SSO/error.html.twig', ['message' => $message]),
            $status
        );
    }

    private function getInfoFromLDAP(array $info): array
    {
        if (!$this->ldapService) {
            throw new \Exception('LDAP Service not enabled.');
        }

        if (!$this->ldapService->isConfigured()) {
            throw new \Exception('LDAP Service not enabled.');
        }

        $ldapInfo = [];

        if (array_key_exists('email', $info)) {
            $ldapInfo = $this->ldapService->getInfoFromEmail($info['email']);
        } elseif (array_key_exists('username', $info)) {
            $ldapInfo = $this->ldapService->getInfoFrom(self::LDAP_ID_KEY, $info['username']);
        }

        return false !== $ldapInfo ? $ldapInfo : [];
    }

    private function getGroup(string $key): Group
    {
        $cleanKey = preg_replace('/\W/', '', $key);

        $group = $this->documentManager->getRepository(Group::class)->findOneBy(['key' => $cleanKey]);
        if ($group) {
            return $group;
        }

        $group = new Group();
        $group->setKey($cleanKey);
        $group->setName($key);
        $group->setOrigin(self::GROUP_ORIGIN);
        $this->groupService->create($group);

        return $group;
    }
}
