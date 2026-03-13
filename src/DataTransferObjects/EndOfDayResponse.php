<?php

namespace Sarkhanrasimoghlu\PashaBank\DataTransferObjects;

use Sarkhanrasimoghlu\PashaBank\Enums\Result;
use Sarkhanrasimoghlu\PashaBank\Enums\ResultCode;

final readonly class EndOfDayResponse
{
    public function __construct(
        public ?Result $result,
        public ?ResultCode $resultCode = null,
        public ?int $creditTransactions = null,
        public ?int $creditReversals = null,
        public ?int $debitTransactions = null,
        public ?int $debitReversals = null,
        public ?float $creditSum = null,
        public ?float $creditReversalSum = null,
        public ?float $debitSum = null,
        public ?float $debitReversalSum = null,
        public array $rawResponse = [],
    ) {}

    public function isSuccessful(): bool
    {
        return $this->result === Result::OK;
    }

    public static function fromApiResponse(array $response): self
    {
        $resultCode = ResultCode::tryFrom(trim($response['RESULT_CODE'] ?? ''));
        $hasStats = $resultCode !== null && str_starts_with($resultCode->value, '5');

        return new self(
            result: Result::tryFrom(trim($response['RESULT'] ?? '')),
            resultCode: $resultCode,
            creditTransactions: $hasStats ? self::intOrNull($response['FLD_074'] ?? null) : null,
            creditReversals: $hasStats ? self::intOrNull($response['FLD_075'] ?? null) : null,
            debitTransactions: $hasStats ? self::intOrNull($response['FLD_076'] ?? null) : null,
            debitReversals: $hasStats ? self::intOrNull($response['FLD_077'] ?? null) : null,
            creditSum: $hasStats ? self::floatOrNull($response['FLD_086'] ?? null) : null,
            creditReversalSum: $hasStats ? self::floatOrNull($response['FLD_087'] ?? null) : null,
            debitSum: $hasStats ? self::floatOrNull($response['FLD_088'] ?? null) : null,
            debitReversalSum: $hasStats ? self::floatOrNull($response['FLD_089'] ?? null) : null,
            rawResponse: $response,
        );
    }

    private static function intOrNull(?string $value): ?int
    {
        return $value !== null && $value !== '' ? (int) $value : null;
    }

    private static function floatOrNull(?string $value): ?float
    {
        return $value !== null && $value !== '' ? (float) $value : null;
    }
}
