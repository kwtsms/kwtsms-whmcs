<?php

declare(strict_types=1);

namespace KwtSMS\WHMCS\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for kwtsms_config() and module structure.
 * No WHMCS runtime required.
 */
class ModuleConfigTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../../';

    protected function setUp(): void
    {
        // Include module file once (WHMCS constant defined in bootstrap)
        require_once self::MODULE_DIR . 'kwtsms.php';
    }

    public function testConfigFunctionExists(): void
    {
        $this->assertTrue(function_exists('kwtsms_config'), 'kwtsms_config() must exist');
    }

    public function testActivateFunctionExists(): void
    {
        $this->assertTrue(function_exists('kwtsms_activate'), 'kwtsms_activate() must exist');
    }

    public function testDeactivateFunctionExists(): void
    {
        $this->assertTrue(function_exists('kwtsms_deactivate'), 'kwtsms_deactivate() must exist');
    }

    public function testOutputFunctionExists(): void
    {
        $this->assertTrue(function_exists('kwtsms_output'), 'kwtsms_output() must exist');
    }

    public function testConfigReturnsRequiredKeys(): void
    {
        $config = kwtsms_config();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('description', $config);
        $this->assertArrayHasKey('version', $config);
        $this->assertArrayHasKey('author', $config);
        $this->assertArrayHasKey('language', $config);
        $this->assertArrayHasKey('fields', $config);
    }

    public function testConfigNameIsKwtsms(): void
    {
        $config = kwtsms_config();
        $this->assertSame('kwtSMS', $config['name']);
    }

    public function testConfigVersionIsValid(): void
    {
        $config = kwtsms_config();
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $config['version']);
    }

    /**
     * 2026-11-05 14:53 +03: the User-Agent app token is kwtsms-whmcs/<module version>, and the
     * kwtsms-php client sends it when the installed library supports app_name, #939.
     */
    public function testAppNameTokenUsesModuleVersion(): void
    {
        $token = \KwtSMS\WHMCS\GatewayManager::appName();
        $this->assertSame('kwtsms-whmcs/' . kwtsms_config()['version'], $token);

        $client = new \KwtSMS\KwtSMS('user', 'pass', 'KWT-SMS', true, '', $token);
        if (!method_exists($client, 'getUserAgent')) {
            $this->markTestSkipped('Installed kwtsms-php has no app_name support yet');
        }
        $this->assertStringStartsWith($token . ' kwtsms-php/', $client->getUserAgent());
    }

    public function testConfigLanguageIsEnglish(): void
    {
        $config = kwtsms_config();
        $this->assertSame('english', $config['language']);
    }

    public function testConfigFieldsIsArray(): void
    {
        $config = kwtsms_config();
        $this->assertIsArray($config['fields']);
    }
}
