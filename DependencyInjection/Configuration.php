<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pumukit_lms');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('allow_create_users_from_request')
            ->defaultTrue()
            ->end()
            ->scalarNode('check_ldap_info_for_permission_profile')
            ->defaultTrue()
            ->info('Check group key and PAS/PDI key to update permission profile to auto publisher')
            ->end()
            ->scalarNode('password')
            ->defaultValue('ThisIsASecretPasswordChangeMe')
            ->info('shared secret between LMS and Pumukit')
            ->end()
            ->scalarNode('role')
            ->defaultValue('owner')
            ->info('Role used to filter persons in multimedia object')
            ->end()
            ->scalarNode('naked_backoffice_domain')
            ->defaultValue('')
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
            ->scalarNode('default_series_title')
            ->defaultValue('My LMS Uploads')
            ->info('Series title for Multimedia Objects uploaded from LMS')
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
