<?php

namespace Pumukit\LmsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PumukitLmsExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Necessary to use the parameters in PumukitNewAdminBundle
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('pumukit_lms.check_ldap_info_for_permission_profile', $config['check_ldap_info_for_permission_profile']);
        $container->setParameter('pumukit_lms.password', $config['password']);
        $container->setParameter('pumukit_lms.role', $config['role']);

        if ($config['naked_backoffice_domain']) {
            $container->setParameter('pumukit.naked_backoffice_domain', $config['naked_backoffice_domain']);
        }

        if ($config['naked_backoffice_background']) {
            $container->setParameter('pumukit.naked_backoffice_background', $config['naked_backoffice_background']);
        }

        if ($config['naked_backoffice_color']) {
            $container->setParameter('pumukit.naked_backoffice_color', $config['naked_backoffice_color']);
        }

        if ($config['naked_custom_css_url']) {
            $container->setParameter('pumukit.naked_custom_css_url', $config['naked_custom_css_url']);
        }

        $container->setParameter('pumukit_lms.default_series_title', $config['default_series_title']);
        $container->setParameter('pumukit_lms.domains', $config['domains']);
    }
}
