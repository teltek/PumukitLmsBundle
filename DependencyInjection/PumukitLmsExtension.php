<?php

declare(strict_types=1);

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
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('pumukit_lms.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('pumukit_lms.password', $config['password']);
        $container->setParameter('pumukit_lms.role', $config['role']);
        $container->setParameter('pumukit_lms.naked_backoffice_domain', $config['naked_backoffice_domain']);
        $container->setParameter('pumukit_lms.naked_backoffice_background', $config['naked_backoffice_background']);
        $container->setParameter('pumukit_lms.naked_backoffice_color', $config['naked_backoffice_color']);
        $container->setParameter('pumukit_lms.naked_custom_css_url', $config['naked_custom_css_url']);
        $container->setParameter('pumukit_lms.allow_create_users_from_request', $config['allow_create_users_from_request']);
        $container->setParameter('pumukit_lms.default_series_title', $config['default_series_title']);
        $container->setParameter('pumukit_lms.domains', $config['domains']);
    }
}
