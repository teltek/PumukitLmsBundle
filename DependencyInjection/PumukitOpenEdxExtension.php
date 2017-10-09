<?php

namespace Pumukit\OpenEdxBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PumukitOpenEdxExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        //Necessary to use the parameters in PumukitNewAdminBundle
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('pumukit_openedx.password', $config['password']);
        $container->setParameter('pumukit_openedx.role', $config['role']);

        if ($config['naked_backoffice_domain']) {
            $container->setParameter('pumukit2.naked_backoffice_domain', $config['naked_backoffice_domain']);
        }

        if ($config['naked_backoffice_background']) {
            $container->setParameter('pumukit2.naked_backoffice_background', $config['naked_backoffice_background']);
        }

        if ($config['naked_backoffice_color']) {
            $container->setParameter('pumukit2.naked_backoffice_color', $config['naked_backoffice_color']);
        }

        if ($config['naked_custom_css_url']) {
            $container->setParameter('pumukit2.naked_custom_css_url', $config['naked_custom_css_url']);
        }

        $container->setParameter('pumukit_openedx.upload_series_title', $config['upload_series_title']);
        $container->setParameter('pumukit_openedx.recording_series_title', $config['recording_series_title']);
        $container->setParameter('pumukit_openedx.open_edx_lms_host', $config['open_edx_lms_host']);
        $container->setParameter('pumukit_openedx.open_edx_cms_host', $config['open_edx_cms_host']);
    }
}
