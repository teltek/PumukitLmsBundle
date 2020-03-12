<?php

namespace Pumukit\LmsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pumukit_lms');

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
            ->arrayNode('domains')
            ->info('Domains to connected to this PuMuKIT')
            ->prototype('scalar')->end()
            ->defaultValue([])
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
