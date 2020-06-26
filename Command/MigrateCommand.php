<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Command;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\TagService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends ContainerAwareCommand
{
    private $dm;
    /** @var TagService */
    private $tagService;
    private $LMSTag;
    private $moodleTag;

    protected function configure(): void
    {
        $this
            ->setName('lms:migrate')
            ->setDescription('Migrate PUCHMOODLE tag of MultimediaObject to PUCHLMS tag')
            ->setHelp(
                <<<'EOT'
Migrate PUCHMOODLE tag of MultimediaObject to PUCHLMS tag
EOT
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->tagService = $this->get('pumukitschema.tag');
        $this->LMSTag = $this->dm->getRepository(Tag::class)->findOneBy(['cod' => 'PUCHLMS']);
        if (!$this->LMSTag) {
            throw new \Exception('Tag PUCHLMS not found');
        }
        $this->moodleTag = $this->dm->getRepository(Tag::class)->findOneBy(['cod' => 'PUCHMOODLE']);
        if (!$this->moodleTag) {
            throw new \Exception('Tag PUCHMoodle not found');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        if (!$multimediaObjects = $this->getAllMultimediaObjects()) {
            $output->writeln('No multimedia object with PUCHMOODLE found');
            return 0;
        }

        $progress = new ProgressBar($output, count($multimediaObjects));
        $progress->start();

        $messages = [];
        foreach ($multimediaObjects as $multimediaObject) {
            $progress->advance();
            $this->changePubChannel($multimediaObject);
            $messages[] = 'Multimedia object with id ' . $multimediaObject->getId() . ' migrate';
        }

        $progress->finish();
        $this->dm->flush();

        foreach ($messages as $message) {
            $output->writeln($message);
        }

        return 0;
    }

    private function getAllMultimediaObjects()
    {
        // Note: Show https://github.com/teltek/PumukitMoodleBundle/blob/master/Command/MoodleInitPubchannelCommand.php#L31
        return $this->dm->getRepository(MultimediaObject::class)->findBy(['tags.cod' => 'PUCHMOODLE']);
    }

    private function changePubChannel(MultimediaObject $multimediaObject): void
    {
        $this->tagService->addTagToMultimediaObject($multimediaObject, $this->LMSTag);
        $this->tagService->removeTagFromMultimediaObject($multimediaObject, $this->moodleTag->getId());
    }
}
