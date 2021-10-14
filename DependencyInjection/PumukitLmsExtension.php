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

        $container->setParameter('pumukit_lms.upload_series_title', $config['upload_series_title']);
        $container->setParameter('pumukit_lms.recording_series_title', $config['recording_series_title']);
        $container->setParameter('pumukit_lms.domains', $config['domains']);
    }
}
