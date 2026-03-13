<?php

namespace Sarkhanrasimoghlu\PashaBank\Configuration;

use Sarkhanrasimoghlu\PashaBank\Contracts\ConfigurationInterface;
use Sarkhanrasimoghlu\PashaBank\Exceptions\InvalidConfigurationException;

final readonly class PashaBankConfiguration implements ConfigurationInterface
{
    public function __construct(
        private string $merchantHandler,
        private string $clientHandler,
        private string $terminalId,
        private string $certificate,
        private string $certificatePassword,
        private string $privateKey,
        private string $privateKeyPassword,
        private string $caCertificate,
        private string $currency,
        private string $language,
        private string $successUrl,
        private string $errorUrl,
        private int $timeout,
        private bool $sslVerify,
        private string $logChannel,
        private string $logLevel,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            merchantHandler: $config['merchant_handler'] ?? '',
            clientHandler: $config['client_handler'] ?? '',
            terminalId: $config['terminal_id'] ?? '',
            certificate: $config['certificate'] ?? '',
            certificatePassword: $config['certificate_password'] ?? '',
            privateKey: $config['private_key'] ?? '',
            privateKeyPassword: $config['private_key_password'] ?? '',
            caCertificate: $config['ca_certificate'] ?? '',
            currency: $config['currency'] ?? '944',
            language: $config['language'] ?? 'az',
            successUrl: $config['success_url'] ?? '',
            errorUrl: $config['error_url'] ?? '',
            timeout: (int) ($config['timeout'] ?? 30),
            sslVerify: (bool) ($config['ssl_verify'] ?? true),
            logChannel: $config['logging']['channel'] ?? 'stack',
            logLevel: $config['logging']['level'] ?? 'info',
        );
    }

    public function getMerchantHandler(): string
    {
        return $this->merchantHandler;
    }

    public function getClientHandler(): string
    {
        return $this->clientHandler;
    }

    public function getTerminalId(): string
    {
        return $this->terminalId;
    }

    public function getCertificate(): string
    {
        return $this->certificate;
    }

    public function getCertificatePassword(): string
    {
        return $this->certificatePassword;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getPrivateKeyPassword(): string
    {
        return $this->privateKeyPassword;
    }

    public function getCaCertificate(): string
    {
        return $this->caCertificate;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getSuccessUrl(): string
    {
        return $this->successUrl;
    }

    public function getErrorUrl(): string
    {
        return $this->errorUrl;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getSslVerify(): bool
    {
        return $this->sslVerify;
    }

    public function getLogChannel(): string
    {
        return $this->logChannel;
    }

    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    public function validate(): void
    {
        $required = [
            'merchant_handler' => $this->merchantHandler,
            'client_handler' => $this->clientHandler,
            'terminal_id' => $this->terminalId,
            'certificate' => $this->certificate,
        ];

        foreach ($required as $key => $value) {
            if (empty($value)) {
                throw InvalidConfigurationException::missingKey($key);
            }
        }
    }
}
