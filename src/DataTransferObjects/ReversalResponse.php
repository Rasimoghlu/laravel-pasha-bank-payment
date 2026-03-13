<?php

namespace Sarkhanrasimoghlu\PashaBank\DataTransferObjects;

use Sarkhanrasimoghlu\PashaBank\Enums\Result;
use Sarkhanrasimoghlu\PashaBank\Enums\ResultCode;

final readonly class ReversalResponse
{
    public function __construct(
        public ?Result $result,
        public ?ResultCode $resultCode = null,
        public array $rawResponse = [],
    ) {}

    public function isSuccessful(): bool
    {
        return $this->result === Result::OK
            && $this->resultCode === ResultCode::ReversalAccepted;
    }

    public static function fromApiResponse(array $response): self
    {
        return new self(
            result: Result::tryFrom(trim($response['RESULT'] ?? '')),
            resultCode: ResultCode::tryFrom(trim($response['RESULT_CODE'] ?? '')),
            rawResponse: $response,
        );
    }
}
