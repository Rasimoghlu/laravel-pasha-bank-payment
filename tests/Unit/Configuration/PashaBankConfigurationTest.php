<?php

namespace Sarkhanrasimoghlu\PashaBank\Tests\Unit\Configuration;

use PHPUnit\Framework\TestCase;
use Sarkhanrasimoghlu\PashaBank\Configuration\PashaBankConfiguration;
use Sarkhanrasimoghlu\PashaBank\Exceptions\InvalidConfigurationException;

class PashaBankConfigurationTest extends TestCase
{
    private function validConfig(): array
    {
        return [
            'merchant_handler' => 'https://ecomm.pashabank.az:18443/ecomm2/MerchantHandler',
            'client_handler' => 'https://ecomm.pashabank.az:8463/ecomm2/ClientHandler',
            'terminal_id' => 'TEST-TERMINAL',
            'certificate' => '/path/to/cert.p12',
            'certificate_password' => 'secret',
            'private_key' => '/path/to/key.pem',
            'private_key_password' => '',
            'ca_certificate' => '/path/to/psroot.pem',
            'currency' => '944',
            'language' => 'az',
            'success_url' => 'https://example.com/success',
            'error_url' => 'https://example.com/error',
            'timeout' => 30,
            'ssl_verify' => true,
            'logging' => [
                'channel' => 'stack',
                'level' => 'info',
            ],
        ];
    }

    public function test_it_creates_from_array(): void
    {
        $config = PashaBankConfiguration::fromArray($this->validConfig());

        $this->assertSame('https://ecomm.pashabank.az:18443/ecomm2/MerchantHandler', $config->getMerchantHandler());
        $this->assertSame('https://ecomm.pashabank.az:8463/ecomm2/ClientHandler', $config->getClientHandler());
        $this->assertSame('TEST-TERMINAL', $config->getTerminalId());
        $this->assertSame('/path/to/cert.p12', $config->getCertificate());
        $this->assertSame('secret', $config->getCertificatePassword());
        $this->assertSame('/path/to/key.pem', $config->getPrivateKey());
        $this->assertSame('/path/to/psroot.pem', $config->getCaCertificate());
        $this->assertSame('944', $config->getCurrency());
        $this->assertSame('az', $config->getLanguage());
        $this->assertSame('https://example.com/success', $config->getSuccessUrl());
        $this->assertSame('https://example.com/error', $config->getErrorUrl());
        $this->assertSame(30, $config->getTimeout());
        $this->assertTrue($config->getSslVerify());
        $this->assertSame('stack', $config->getLogChannel());
        $this->assertSame('info', $config->getLogLevel());
    }

    public function test_it_uses_defaults_for_missing_values(): void
    {
        $config = PashaBankConfiguration::fromArray([]);

        $this->assertSame('944', $config->getCurrency());
        $this->assertSame('az', $config->getLanguage());
        $this->assertSame(30, $config->getTimeout());
        $this->assertTrue($config->getSslVerify());
    }

    public function test_validate_passes_with_valid_config(): void
    {
        $config = PashaBankConfiguration::fromArray($this->validConfig());
        $config->validate();

        $this->assertTrue(true);
    }

    public function test_validate_fails_without_merchant_handler(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('merchant_handler');

        $data = $this->validConfig();
        $data['merchant_handler'] = '';
        PashaBankConfiguration::fromArray($data)->validate();
    }

    public function test_validate_fails_without_terminal_id(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('terminal_id');

        $data = $this->validConfig();
        $data['terminal_id'] = '';
        PashaBankConfiguration::fromArray($data)->validate();
    }

    public function test_validate_fails_without_certificate(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('certificate');

        $data = $this->validConfig();
        $data['certificate'] = '';
        PashaBankConfiguration::fromArray($data)->validate();
    }

    public function test_validate_fails_without_client_handler(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('client_handler');

        $data = $this->validConfig();
        $data['client_handler'] = '';
        PashaBankConfiguration::fromArray($data)->validate();
    }

    public function test_validate_fails_without_success_url(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('success_url');

        $data = $this->validConfig();
        $data['success_url'] = '';
        PashaBankConfiguration::fromArray($data)->validate();
    }

    public function test_validate_fails_without_error_url(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('error_url');

        $data = $this->validConfig();
        $data['error_url'] = '';
        PashaBankConfiguration::fromArray($data)->validate();
    }

    public function test_validate_passes_without_certificate_when_ssl_disabled(): void
    {
        $data = $this->validConfig();
        $data['certificate'] = '';
        $data['ssl_verify'] = false;

        $config = PashaBankConfiguration::fromArray($data);
        $config->validate();

        $this->assertTrue(true);
    }
}
