<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const INSECURE_PASSWORD = 'ThisIsASecretPasswordChangeMe';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pumukit_lms');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->validate()
            ->ifTrue(static fn (array $config): bool => $config['enable'] && in_array(trim((string) $config['password']), ['', self::INSECURE_PASSWORD], true))
            ->thenInvalid('pumukit_lms needs its own password, shared with the LMS plugins, when it is enabled')
            ->end()
            ->children()
            ->booleanNode('enable')
            ->defaultTrue()
            ->info('If false, the LMS routes give 404, nothing else of the bundle is loaded and the password is not required')
            ->end()
            ->scalarNode('allow_create_users_from_request')
            ->defaultTrue()
            ->end()
            ->scalarNode('check_ldap_info_for_permission_profile')
            ->defaultTrue()
            ->info('Check group key and PAS/PDI key to update permission profile to auto publisher')
            ->end()
            ->scalarNode('password')
            ->defaultNull()
            ->info('Shared secret between the LMS plugins and PuMuKIT. Required when the bundle is enabled')
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
            ->enumNode('default_upload_profile')
            ->values(['master_copy', 'master_encoded', 'broadcastable'])
            ->defaultValue('broadcastable')
            ->info('Default profile for uploading Multimedia Objects. Allowed values: master_copy, master_encoded, broadcastable')
            ->end()
            ->scalarNode('lms_upload_url')
            ->defaultValue('tus')
            ->info('URL to process uploaded files in Moodle. Ex: https://localhost/moodle/tus')
            ->end()
            ->scalarNode('lti_learner_profile')
            ->defaultNull()
            ->info('Permission profile of the LTI learners. If null, the learners only watch the videos and are not created as users. "Auto Publisher" restores the behaviour of 5.x')
            ->end()
            ->arrayNode('domains')
            ->info('Domains connected to this PuMuKIT. They can also embed the backoffice in an iframe')
            ->prototype('scalar')->end()
            ->defaultValue([])
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
