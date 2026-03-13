<?php

namespace Sarkhanrasimoghlu\PashaBank\Exceptions;

use Throwable;

class HttpException extends PashaBankException
{
    public static function connectionFailed(string $url, ?Throwable $previous = null): self
    {
        return new self(
            "Failed to connect to: {$url}",
            0,
            $previous,
            ['url' => $url],
        );
    }

    public static function timeout(string $url, int $timeout): self
    {
        return new self(
            "Request to {$url} timed out after {$timeout}s",
            0,
            null,
            ['url' => $url, 'timeout' => $timeout],
        );
    }

    public static function sslError(string $message, ?Throwable $previous = null): self
    {
        return new self(
            "SSL certificate error: {$message}",
            0,
            $previous,
            ['ssl_error' => $message],
        );
    }

    public static function serverError(int $statusCode, string $body): self
    {
        return new self(
            "Server returned HTTP {$statusCode}",
            $statusCode,
            null,
            ['status_code' => $statusCode, 'body' => $body],
        );
    }
}
