<?php

namespace Pumukit\OpenEdxBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class OpenEdxInitResourcesCommand extends ContainerAwareCommand
{
    const OVERRIDE_DATA_DIR = 'Resources/data/override';

    protected function configure()
    {
        $this
          ->setName('openedx:init:resources')
          ->addArgument('version', InputArgument::REQUIRED, 'Select the version of PuMuKIT to override PumukitNewAdminBundle list template: 2.3.x, 2.4.x')
          ->setDescription('Initialize the resources necessary to add a button to insert a VoD into OpenEdx/Moodle')
          ->setHelp(<<<EOT
Initialize the resources necessary to add a button to insert a VoD into OpenEdx/Moodle. It copies the global resources from Resources/data/override bundle dir to
the app/Resources project dir.

cp ../Resources/data/override/PumukitNewAdminBundle/views/MultimediaObject/list.html.twig  app/Resources/PumukitNewAdminBundle/views/MultimediaObject/list.html.twig


EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getArgument('version');

        $finder = new Finder();
        $fs = new Filesystem();

        $fromDir = realpath(__DIR__.'/../'.self::OVERRIDE_DATA_DIR.'/'.$version);
        $toDir = $this->getContainer()->get('kernel')->getRootDir().'/Resources';

        $output->writeln('Coping resources from <info>'.$fromDir.'</info> to <info>'.$toDir.'</info>:');

        $finder->files()->notName('*~')->in($fromDir);
        foreach ($finder as $file) {
            $from = $file->getRealpath();
            $to = str_replace($fromDir, $toDir, $from);
            $output->writeln('  * Coping file from <info>'.$from.'</info>');
            $output->writeln('    to <info>'.$to.'</info>');
            $fs->copy($from, $to, true);
        }
    }
}
