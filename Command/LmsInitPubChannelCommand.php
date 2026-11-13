<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\LmsBundle\PumukitLmsBundle;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AsCommand(name: 'pumukit:lms:init:pubchannel', description: 'Create the LMS publication channel if it does not exist')]
#[AutoconfigureTag('pumukit.setup')]
class LmsInitPubChannelCommand extends Command
{
    public function __construct(private readonly DocumentManager $documentManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Creates the PUCHLMS publication channel, required to publish objects exclusively on the LMS platform.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repository = $this->documentManager->getRepository(Tag::class);
        if ($repository->findOneBy(['cod' => PumukitLmsBundle::LMS_TAG_CODE])) {
            $output->writeln(sprintf('The tag %s already exists.', PumukitLmsBundle::LMS_TAG_CODE));

            return Command::SUCCESS;
        }

        $parent = $repository->findOneBy(['cod' => 'PUBCHANNELS']);
        if (!$parent instanceof Tag) {
            $output->writeln('<error>There is no tag PUBCHANNELS to be the parent of the LMS publication channel: run pumukit:init:repo first.</error>');

            return Command::FAILURE;
        }

        $tag = new Tag();
        $tag->setCod(PumukitLmsBundle::LMS_TAG_CODE);
        $tag->setMetatag(false);
        $tag->setDisplay(true);
        foreach (['es', 'gl', 'en'] as $locale) {
            $tag->setTitle('LMS', $locale);
        }
        $tag->setParent($parent);
        $this->documentManager->persist($tag);
        $this->documentManager->flush();

        $output->writeln(sprintf('The tag %s is created.', PumukitLmsBundle::LMS_TAG_CODE));

        return Command::SUCCESS;
    }
}
