<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\Command;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @coversNothing
 */
class MigrateCommandTest extends PumukitTestCase
{
    public function testTheMoodleVideosGetTheLmsTag(): void
    {
        $moodle = $this->createTag('PUCHMOODLE');
        $this->createTag('PUCHLMS');
        $series = new Series();
        $series->setNumericalID(1);
        $this->dm->persist($series);
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(1);
        $multimediaObject->setSeries($series);
        $multimediaObject->addTag($moodle);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $tester = new CommandTester((new Application(self::$kernel))->find('pumukit:lms:migrate'));

        $this->assertSame(Command::SUCCESS, $tester->execute([]), $tester->getDisplay());
        $this->dm->clear();
        $this->assertTrue($this->dm->getRepository(MultimediaObject::class)->find($multimediaObject->getId())->containsTagWithCod('PUCHLMS'));
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
