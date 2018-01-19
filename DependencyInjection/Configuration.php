<?php

namespace Pumukit\OpenEdxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pumukit_open_edx');

        $rootNode
          ->children()
            ->scalarNode('password')
              ->defaultValue('ThisIsASecretPasswordChangeMe')
              ->info('shared secret between Open edX and Pumukit')
            ->end()
            ->scalarNode('role')
              ->defaultValue('owner')
              ->info('Role used to filter persons in multimedia object')
            ->end()
            ->scalarNode('naked_backoffice_domain')
              ->defaultFalse()
              ->info('Domain or subdomain used to access into the naked backoffice')
            ->end()
            ->scalarNode('naked_backoffice_background')
              ->defaultValue('white')
              ->info('CSS color used in the naked backoffice background')
            ->end()
            ->scalarNode('naked_backoffice_color')
              ->defaultValue('#ED6D00')
              ->info('CSS color used in the naked backoffice as main color')
            ->end()
            ->scalarNode('naked_custom_css_url')
              ->defaultValue(null)
              ->info('Custom CSS URL')
            ->end()
            ->scalarNode('upload_series_title')
              ->defaultValue('My Uploads')
              ->info('Series title for Multimedia Objects uploaded from Open edX')
            ->end()
            ->scalarNode('recording_series_title')
              ->defaultValue('My Recordings')
              ->info('Series title for Multimedia Objects recorded from Open edX')
            ->end()
            ->scalarNode('open_edx_lms_host')
              ->defaultFalse()
              ->info('Domain of the Open edX LMS connected to this PuMuKIT')
            ->end()
            ->scalarNode('open_edx_cms_host')
              ->defaultFalse()
              ->info('Domain of the Open edX CMS connected to this PuMuKIT')
            ->end()
            ->scalarNode('moodle_host')
              ->defaultFalse()
              ->info('Domain of the Moodle connected to this PuMuKIT')
            ->end()
          ->end()
        ;

        return $treeBuilder;
    }
}
