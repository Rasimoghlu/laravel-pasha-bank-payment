<?php

namespace Sarkhanrasimoghlu\PashaBank\Contracts;

interface ConfigurationInterface
{
    public function getMerchantHandler(): string;

    public function getClientHandler(): string;

    public function getTerminalId(): string;

    public function getCertificate(): string;

    public function getCertificatePassword(): string;

    public function getPrivateKey(): string;

    public function getPrivateKeyPassword(): string;

    public function getCaCertificate(): string;

    public function getCurrency(): string;

    public function getLanguage(): string;

    public function getSuccessUrl(): string;

    public function getErrorUrl(): string;

    public function getTimeout(): int;

    public function getSslVerify(): bool;

    public function getLogChannel(): string;

    public function getLogLevel(): string;

    public function validate(): void;
}
