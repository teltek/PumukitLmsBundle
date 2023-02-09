<?php

namespace Pumukit\LmsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Component\HttpFoundation\RequestStack;

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

    private $dm;
    private $permissionProfileService;
    private $userService;
    private $personService;
    private $groupService;
    private $password;
    private $domain;
    private $ldapService;
    private $requestStack;

    public function __construct(
        DocumentManager $dm,
        PermissionProfileService $permissionProfileService,
        UserService $userService,
        PersonService $personService,
        GroupService $groupService,
        $password,
        $domain,
        $ldapService = null,
        RequestStack $requestStack = null
    ) {
        $this->dm = $dm;
        $this->permissionProfileService = $permissionProfileService;
        $this->userService = $userService;
        $this->personService = $personService;
        $this->groupService = $groupService;
        $this->password = $password;
        $this->domain = $domain;
        $this->ldapService = $ldapService;
        $this->requestStack = $requestStack;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getHash(string $email): string
    {
        $date = date('d/m/Y');

        return md5($email.$this->password.$date.$this->domain);
    }

    public function validateHash(string $hash, string $email): bool
    {
        return $hash === $this->getHash($email);
    }

    public function createUser(array $info): User
    {
        if (!$this->ldapService) {
            throw new \Exception('LDAP Service not enabled.');
        }
        if (!$this->ldapService->isConfigured()) {
            throw new \Exception('LDAP Service not enabled.');
        }

        if (array_key_exists('email', $info)) {
            $info = $this->ldapService->getInfoFromEmail($info['email']);
        } elseif (array_key_exists('username', $info)) {
            $info = $this->ldapService->getInfoFrom(self::LDAP_ID_KEY, $info['username']);
        }

        if (!isset($info) || !$info) {
            throw new \RuntimeException('User not found.');
        }

        if (!isset($info[self::GROUP_KEY][0])
            || !in_array($info[self::GROUP_KEY][0], [self::LDAP_PAS, self::LDAP_PDI])) {
            throw new \RuntimeException('User invalid.');
        }

        $user = $this->createUserWithInfo($info);
        $group = $this->getGroup($info[self::GROUP_KEY][0]);
        $this->userService->addGroup($group, $user, true, false);

        $this->personService->referencePersonIntoUser($user);

        return $user;
    }

    public function promoteUser(User $user): void
    {
        if (!$this->ldapService) {
            throw new \Exception('LDAP Service not enabled.');
        }

        if (!$this->ldapService->isConfigured()) {
            throw new \Exception('LDAP Service not enabled.');
        }

        $updateUser = false;
        $permissionProfileViewer = $this->permissionProfileService->getByName(self::PERMISSION_PROFILE_VIEWER);
        $permissionProfileAutoPub = $this->permissionProfileService->getByName(self::PERMISSION_PROFILE_AUTO);

        $info = $this->ldapService->getInfoFromEmail($user->getEmail());
        if (!$info) {
            throw new \RuntimeException('User not found.');
        }

        if ($permissionProfileViewer == $user->getPermissionProfile()) {
            if (!isset($info[self::GROUP_KEY][0])
                || !in_array($info[self::GROUP_KEY][0], [self::LDAP_PAS, self::LDAP_PDI])) {
                throw new \RuntimeException('User invalid.');
            }

            $user->setPermissionProfile($permissionProfileAutoPub);
            $updateUser = true;
        }

        if ($this->getFullNameOfUser($info, $user->getFullname()) !== $user->getFullname()) {
            $user->setFullname($this->getFullNameOfUser($info, $user->getFullname()));
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
        $fullName = $this->getFullNameOfUser($info, $username);

        return $this->createUserByUsernameAndEmail($username, $email, $fullName);
    }

    public function createUserByUsernameAndEmail($username, $email, $fullName): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setFullname($fullName);

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

    private function getGroup(string $key)
    {
        $cleanKey = preg_replace('/\W/', '', $key);

        $group = $this->dm->getRepository(Group::class)->findOneBy(['key' => $cleanKey]);
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

    private function getFullNameOfUser(array $info, $username): string
    {
        return $info['cn'] ?? $username;
    }
}
