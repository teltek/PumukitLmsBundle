<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\LmsBundle\PumukitLmsBundle;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LmsInitPubChannelCommand extends Command
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:lms:init:pubchannel')
            ->setDescription('Loads the LMS pubchannel to your database')
            ->setHelp(
                <<<'EOT'
Command to load the PUCHLMS pubchannel to the db. Required to publish objects exclusively on the LMS platform.
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lmsPublicationChannelTag = $this->createTagWithCode(PumukitLmsBundle::LMS_TAG_CODE, 'LMS', 'PUBCHANNELS', false);
        $this->documentManager->persist($lmsPublicationChannelTag);
        $this->documentManager->flush();

        $output->writeln('Tag persisted - new id: '.$lmsPublicationChannelTag->getId().' cod: '.$lmsPublicationChannelTag->getCod());

        return 0;
    }

    private function createTagWithCode(string $code, string $title, string $tagParentCode = null, bool $metatag = false): Tag
    {
        if ($tag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $code])) {
            throw new \Exception('Nothing done - Tag retrieved from DB id: '.$tag->getId().' cod: '.$tag->getCod());
        }
        $tag = new Tag();
        $tag->setCod($code);
        $tag->setMetatag($metatag);
        $tag->setDisplay(true);
        $tag->setTitle($title, 'es');
        $tag->setTitle($title, 'gl');
        $tag->setTitle($title, 'en');
        if ($tagParentCode) {
            if ($parent = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $tagParentCode])) {
                $tag->setParent($parent);
            } else {
                throw new \Exception('Nothing done - There is no tag in the database with code '.$tagParentCode.' to be the parent tag');
            }
        }
        $this->documentManager->persist($tag);
        $this->documentManager->flush();

        return $tag;
    }
}
