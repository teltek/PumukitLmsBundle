<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Pumukit\LmsBundle\DependencyInjection\PumukitLmsExtension;
use Pumukit\LmsBundle\EventListener\DisabledListener;
use Pumukit\LmsBundle\Services\MultimediaObjectVoter;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 *
 * @coversNothing
 */
class PumukitLmsExtensionTest extends TestCase
{
    public function testTheDisabledBundleOnlyRegistersTheNotFoundListener(): void
    {
        $container = $this->load(['enable' => false]);

        $this->assertSame([DisabledListener::class], $this->lmsServices($container));
        $this->assertFalse($container->hasParameter('pumukit.naked_menu_templates'));
    }

    public function testTheEnabledBundleRegistersItsServicesWithoutTheNotFoundListener(): void
    {
        $container = $this->load(['password' => 'secret']);

        $this->assertTrue($container->hasDefinition(MultimediaObjectVoter::class));
        $this->assertFalse($container->hasDefinition(DisabledListener::class));
        $this->assertSame([PumukitLmsExtension::NAKED_MENU_TEMPLATE], $container->getParameter('pumukit.naked_menu_templates'));
    }

    private function load(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new PumukitLmsExtension())->load([$config], $container);

        return $container;
    }

    private function lmsServices(ContainerBuilder $container): array
    {
        return array_values(array_filter(array_keys($container->getDefinitions()), static fn (string $id): bool => str_starts_with($id, 'Pumukit\LmsBundle\\')));
    }
}
