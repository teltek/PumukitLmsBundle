<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\LmsBundle\PumukitLmsBundle;
use Pumukit\LmsBundle\Services\ConfigurationService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

abstract class LmsTestCase extends PumukitTestCase
{
    protected KernelBrowser $client;
    protected Role $owner;
    private int $users = 0;

    public function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();

        $this->owner = new Role();
        $this->owner->setCod('owner');
        $this->dm->persist($this->owner);
        $this->dm->flush();
    }

    protected function user(string $scope = PermissionProfile::SCOPE_PERSONAL, ?string $username = null): User
    {
        ++$this->users;
        $username ??= 'teacher'.$this->users;
        $person = new Person();
        $person->setName($username);
        $person->setEmail($username.'@example.com');
        $this->dm->persist($person);

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@example.com');
        $user->setPerson($person);
        $user->addRole($scope);
        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }

    protected function ownedSeries(User $user, string $title = 'Course'): Series
    {
        $factory = self::getContainer()->get(FactoryService::class);
        $series = $factory->createSeries(null, ['en' => $title]);
        $prototype = $this->dm->getRepository(MultimediaObject::class)->findPrototype($series);
        $prototype->addPersonWithRole($user->getPerson(), $this->owner);
        $this->dm->flush();

        return $series;
    }

    protected function lmsSeries(): Series
    {
        $series = self::getContainer()->get(FactoryService::class)->createSeries(null, ['en' => 'My LMS Uploads']);
        $series->setProperty(PumukitLmsBundle::PROPERTY_LMS, true);
        $this->dm->flush();

        return $series;
    }

    protected function hash(string $value): string
    {
        return self::getContainer()->get(ConfigurationService::class)->generateHash($value);
    }
}
