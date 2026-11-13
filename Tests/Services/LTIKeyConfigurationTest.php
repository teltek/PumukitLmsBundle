<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Tests\Services;

use PHPUnit\Framework\TestCase;
use Pumukit\LmsBundle\Services\LTIKeyConfiguration;

/**
 * @internal
 *
 * @coversNothing
 */
class LTIKeyConfigurationTest extends TestCase
{
    private string $privateKey;

    protected function setUp(): void
    {
        $this->privateKey = sys_get_temp_dir().'/'.uniqid('lti_', true).'/private.pem';
        mkdir(dirname($this->privateKey));
        openssl_pkey_export_to_file(openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]), $this->privateKey);
    }

    protected function tearDown(): void
    {
        unlink($this->privateKey);
        rmdir(dirname($this->privateKey));
    }

    public function testTheKeyIsReadFromTheConfiguredPath(): void
    {
        $configuration = new LTIKeyConfiguration($this->privateKey);

        $this->assertTrue($configuration->hasKeys());
        $this->assertSame(file_get_contents($this->privateKey), $configuration->privateKeyContent());

        $key = $configuration->jwksFromJwkKey()['keys'][0];
        $this->assertSame('RSA', $key['kty']);
        $this->assertSame('RS256', $key['alg']);
        $this->assertSame($configuration->kidFromJwkKey(), $key['kid']);
    }

    public function testAMissingKeyIsNotConfigured(): void
    {
        $this->assertFalse((new LTIKeyConfiguration(dirname($this->privateKey).'/missing.pem'))->hasKeys());
    }
}
