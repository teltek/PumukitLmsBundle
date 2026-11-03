<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\DependencyInjection;

use Pumukit\CoreBundle\DependencyInjection\FrontendAssets;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

class PumukitLmsExtension extends Extension implements PrependExtensionInterface
{
    public const NAKED_MENU_TEMPLATE = '@PumukitLms/LTI/naked_menu.html.twig';

    public function prepend(ContainerBuilder $container): void
    {
        FrontendAssets::register($container, dirname(__DIR__), '@pumukit/lms');
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('pumukit_lms.check_ldap_info_for_permission_profile', $config['check_ldap_info_for_permission_profile']);
        $container->setParameter('pumukit_lms.enable', $config['enable']);
        $container->setParameter('pumukit_lms.password', (string) $config['password']);
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
        $container->setParameter('pumukit_lms.lti_learner_profile', $config['lti_learner_profile']);

        if ($config['enable']) {
            $templates = $container->hasParameter('pumukit.naked_menu_templates') ? $container->getParameter('pumukit.naked_menu_templates') : [];
            $container->setParameter('pumukit.naked_menu_templates', array_values(array_unique([...$templates, self::NAKED_MENU_TEMPLATE])));
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('pumukit_lms.yaml');
    }
}
