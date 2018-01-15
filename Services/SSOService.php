<?php

namespace Pumukit\OpenEdxBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\UserService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\Group;

class SSOService
{
    const USER_ORIGIN = 'openedx';
    const GROUP_KEY = 'edupersonprimaryaffiliation';
    const LDAP_PDI = 'PDI';
    const LDAP_PAS = 'PAS';
    const LDAP_ID_KEY = 'uid';
    const PERMISSION_PROFILE_AUTO = 'Auto Publisher';
    const PERMISSION_PROFILE_VIEWER = 'Viewer';
    const GROUP_ORIGIN = 'cas';

    private $dm;
    private $permissionProfileService;
    private $userService;
    private $personService;
    private $groupService;
    private $password;
    private $domain;
    private $ldapService = null;
    private $groupRepo;

    /**
     * Constructor.
     *
     * @param DocumentManager          $dm
     * @param PermissionProfileService $permissionProfileService
     * @param UserService              $userService
     * @param PersonService            $personService
     * @param GroupService             $groupService
     * @param string                   $password
     * @param string                   $domain
     * @param LDAPService|null         $ldapService
     */
    public function __construct(DocumentManager $dm, PermissionProfileService $permissionProfileService, UserService $userService, PersonService $personService, GroupService $groupService, $password, $domain, $ldapService = null)
    {
        $this->dm = $dm;
        $this->permissionProfileService = $permissionProfileService;
        $this->userService = $userService;
        $this->personService = $personService;
        $this->groupService = $groupService;
        $this->password = $password;
        $this->domain = $domain;
        $this->ldapService = $ldapService;
        $this->groupRepo = $this->dm->getRepository('PumukitSchemaBundle:Group');
    }

    /**
     * Get Pumukit2 Domain for backoffice.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Get hash for user.
     *
     * @param string $email
     *
     * @return string
     */
    public function getHash($email)
    {
        $date = date('d/m/Y');

        return md5($email.$this->password.$date.$this->domain);
    }

    /**
     * Validate domain.
     *
     * @param string $domain
     *
     * @return bool TRUE if $domain is equals to the given domain, FALSE otherwise
     */
    public function validateDomain($domain)
    {
        return $domain === $this->domain;
    }

    /**
     * Validate hash.
     *
     * @param string $hash
     *
     * @return bool TRUE if $hash is equals to the given hash, FALSE otherwise
     */
    public function validateHash($hash, $email)
    {
        return $hash === $this->getHash($email);
    }

    /**
     * Create user from ldap.
     *
     * @param array $info
     *
     * @return User
     */
    public function createUser($info)
    {
        if (!$this->ldapService) {
            throw new \Exception('LDAP Service not enabled.');
        }
        if (!$this->ldapService->isEnabled()) {
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

        if (!isset($info[self::GROUP_KEY][0]) ||
            !in_array($info[self::GROUP_KEY][0], array(self::LDAP_PAS, self::LDAP_PDI))) {
            throw new \RuntimeException('User invalid.');
        }

        $username = $info[self::LDAP_ID_KEY][0];
        $email = $info['mail'][0];

        $user = $this->createUserWithInfo($username, $email);
        $group = $this->getGroup($info[self::GROUP_KEY][0]);
        $this->userService->addGroup($group, $user, true, false);

        $this->personService->referencePersonIntoUser($user);

        return $user;
    }

    /**
     * Promote user
     * from Viewer to Auto Publisher.
     *
     * @param User $user
     */
    public function promoteUser(User $user)
    {
        $permissionProfileViewer = $this->permissionProfileService->getByName(self::PERMISSION_PROFILE_VIEWER);
        $permissionProfileAutoPub = $this->permissionProfileService->getByName(self::PERMISSION_PROFILE_AUTO);

        if ($permissionProfileViewer == $user->getPermissionProfile()) {
            $info = $this->ldapService->getInfoFromEmail($user->getEmail());

            if (!$info) {
                throw new \RuntimeException('User not found.');
            }
            if (!isset($info[self::GROUP_KEY][0]) ||
                !in_array($info[self::GROUP_KEY][0], array(self::LDAP_PAS, self::LDAP_PDI))) {
                throw new \RuntimeException('User invalid.');
            }

            $user->setPermissionProfile($permissionProfileAutoPub);
            $this->userService->update($user, true, false);
        }
    }

    private function createUserWithInfo($username, $email)
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);

        $permissionProfile = $this->permissionProfileService->getByName(self::PERMISSION_PROFILE_AUTO);
        $user->setPermissionProfile($permissionProfile);
        $user->setOrigin(self::USER_ORIGIN);
        $user->setEnabled(true);

        $userService->create($user);

        return $user;
    }

    private function getGroup($key)
    {
        $cleanKey = preg_replace('/\W/', '', $key);

        $group = $this->groupRepo->findOneByKey($cleanKey);
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
