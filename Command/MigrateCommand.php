<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\LmsBundle\PumukitLmsBundle;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\TagService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
    private $documentManager;
    private $tagService;
    private $LMSTag;

    public function __construct(DocumentManager $documentManager, TagService $tagService)
    {
        $this->documentManager = $documentManager;
        $this->tagService = $tagService;
        if (!$this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => PumukitLmsBundle::LMS_TAG_CODE])) {
            throw new \Exception('Tag PUCHLMS not found. Please initialize it using pumukit:lms:init:pubchannel command');
        }
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:lms:migrate')
            ->setDescription('Migrate PUCHMOODLE tag of MultimediaObject to PUCHLMS tag')
            ->setHelp(
                <<<'EOT'
Migrate PUCHMOODLE tag of MultimediaObject to PUCHLMS tag
EOT
            )
        ;
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
            $messages[] = 'Multimedia object with id '.$multimediaObject->getId().' migrate';
        }

        $progress->finish();
        $this->documentManager->flush();

        foreach ($messages as $message) {
            $output->writeln($message);
        }

        return 0;
    }

    private function getAllMultimediaObjects()
    {
        // Note: Show https://github.com/teltek/PumukitMoodleBundle/blob/master/Command/MoodleInitPubchannelCommand.php#L31
        return $this->documentManager->getRepository(MultimediaObject::class)->findBy(['tags.cod' => 'PUCHMOODLE']);
    }

    private function changePubChannel(MultimediaObject $multimediaObject): void
    {
        $this->tagService->addTagToMultimediaObject($multimediaObject, $this->LMSTag);
    }
}
