<?php

namespace Sarkhanrasimoghlu\PashaBank\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Declined = 'declined';
    case Reversed = 'reversed';
    case AutoReversed = 'autoreversed';
    case Refunded = 'refunded';
    case Timeout = 'timeout';

    public static function fromResult(Result $result): self
    {
        return match ($result) {
            Result::OK => self::Succeeded,
            Result::Failed => self::Failed,
            Result::Created, Result::Pending => self::Pending,
            Result::Declined => self::Declined,
            Result::Reversed => self::Reversed,
            Result::AutoReversed => self::AutoReversed,
            Result::Timeout => self::Timeout,
        };
    }
}
