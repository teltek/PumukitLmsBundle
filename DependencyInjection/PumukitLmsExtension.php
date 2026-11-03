<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

class PumukitLmsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('pumukit_lms.check_ldap_info_for_permission_profile', $config['check_ldap_info_for_permission_profile']);
        $container->setParameter('pumukit_lms.password', $config['password']);
        $container->setParameter('pumukit_lms.role', $config['role']);
        $container->setParameter('pumukit_lms.naked_backoffice_domain', $config['naked_backoffice_domain']);
        $container->setParameter('pumukit_lms.naked_backoffice_background', $config['naked_backoffice_background']);
        $container->setParameter('pumukit_lms.naked_backoffice_color', $config['naked_backoffice_color']);
        $container->setParameter('pumukit_lms.naked_custom_css_url', $config['naked_custom_css_url']);
        $container->setParameter('pumukit_lms.allow_create_users_from_request', $config['allow_create_users_from_request']);
        $container->setParameter('pumukit_lms.default_series_title', $config['default_series_title']);
        $container->setParameter('pumukit_lms.default_upload_profile', $config['default_upload_profile']);
        $container->setParameter('pumukit_lms.lms_upload_url', $config['lms_upload_url']);
        $container->setParameter('pumukit_lms.domains', $config['domains']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('pumukit_lms.yaml');
    }
}
