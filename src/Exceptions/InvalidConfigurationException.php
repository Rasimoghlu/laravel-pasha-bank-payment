<?php

namespace Sarkhanrasimoghlu\PashaBank\Exceptions;

class InvalidConfigurationException extends PashaBankException
{
    public static function missingKey(string $key): self
    {
        return new self("Missing required configuration key: {$key}", context: ['key' => $key]);
    }

    public static function invalidCertificate(string $path): self
    {
        return new self("Certificate file not found or not readable: {$path}", context: ['path' => $path]);
    }
}
