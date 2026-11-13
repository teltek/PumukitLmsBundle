<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\Command;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @coversNothing
 */
class LmsInitPubChannelCommandTest extends PumukitTestCase
{
    public function testItCreatesTheTagsOnlyOnce(): void
    {
        $this->createTag('PUBCHANNELS');

        $tester = $this->tester();
        $this->assertSame(Command::SUCCESS, $tester->execute([]), $tester->getDisplay());
        $this->assertStringContainsString('The tag PUCHLMS is created.', $tester->getDisplay());

        $tester = $this->tester();
        $this->assertSame(Command::SUCCESS, $tester->execute([]), $tester->getDisplay());
        $this->assertStringContainsString('already exists', $tester->getDisplay());

        $this->dm->clear();
        $this->assertCount(1, $this->dm->getRepository(Tag::class)->findBy(['cod' => 'PUCHLMS']));
    }

    public function testItFailsWithoutTheParentTag(): void
    {
        $tester = $this->tester();

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $this->assertStringContainsString('run pumukit:init:repo first', $tester->getDisplay());
    }

    private function tester(): CommandTester
    {
        return new CommandTester((new Application(self::$kernel))->find('pumukit:lms:init:pubchannel'));
    }

    private function createTag(string $code): Tag
    {
        $tag = new Tag();
        $tag->setCod($code);
        $this->dm->persist($tag);
        $this->dm->flush();

        return $tag;
    }
}
