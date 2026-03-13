<?php

namespace Sarkhanrasimoghlu\PashaBank\Exceptions;

class InvalidPaymentException extends PashaBankException
{
    public static function invalidAmount(float $amount): self
    {
        return new self("Invalid amount: {$amount}. Amount must be greater than 0.", context: ['amount' => $amount]);
    }

    public static function missingTransactionId(): self
    {
        return new self('Transaction ID is required.');
    }

    public static function missingClientIp(): self
    {
        return new self('Client IP address is required.');
    }

    public static function descriptionTooLong(): self
    {
        return new self('Description must not exceed 125 characters.');
    }
}
