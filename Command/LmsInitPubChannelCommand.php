<?php

namespace Pumukit\LmsBundle\Command;

use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LmsInitPubChannelCommand extends ContainerAwareCommand
{
    private $dm;
    private $tagRepo;

    protected function configure()
    {
        $this
            ->setName('lms:init:pubchannel')
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
        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->tagRepo = $this->dm->getRepository(Tag::class);

        $lmsPublicationChannelTag = $this->createTagWithCode('PUCHLMS', 'LMS', 'PUBCHANNELS', false);
        $this->dm->persist($lmsPublicationChannelTag);
        $this->dm->flush();

        $output->writeln('Tag persisted - new id: '.$lmsPublicationChannelTag->getId().' cod: '.$lmsPublicationChannelTag->getCod());

        return 0;
    }

    private function createTagWithCode(string $code, string $title, string $tagParentCode = null, bool $metatag = false): Tag
    {
        if ($tag = $this->tagRepo->findOneBy(['cod' => $code])) {
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
            if ($parent = $this->tagRepo->findOneBy(['cod' => $tagParentCode])) {
                $tag->setParent($parent);
            } else {
                throw new \Exception('Nothing done - There is no tag in the database with code '.$tagParentCode.' to be the parent tag');
            }
        }
        $this->dm->persist($tag);
        $this->dm->flush();

        return $tag;
    }
}
