<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Pumukit\LmsBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * @internal
 *
 * @coversNothing
 */
class ConfigurationTest extends TestCase
{
    public function testTheEnabledBundleNeedsItsOwnPassword(): void
    {
        foreach ([[], ['password' => ''], ['password' => Configuration::INSECURE_PASSWORD]] as $config) {
            try {
                $this->process($config);
                $this->fail(json_encode($config));
            } catch (InvalidConfigurationException $exception) {
                $this->assertStringContainsString('needs its own password', $exception->getMessage());
            }
        }

        $config = $this->process(['password' => 'secret']);
        $this->assertTrue($config['enable']);
        $this->assertNull($config['lti_learner_profile']);
        $this->assertSame('%kernel.project_dir%/config/lti/keys/private.pem', $config['lti_private_key']);
    }

    public function testTheDisabledBundleNeedsNoPassword(): void
    {
        $this->assertFalse($this->process(['enable' => false])['enable']);
    }

    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }
}
