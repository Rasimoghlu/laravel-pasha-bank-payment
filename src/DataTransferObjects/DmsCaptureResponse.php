<?php

namespace Sarkhanrasimoghlu\PashaBank\DataTransferObjects;

use Sarkhanrasimoghlu\PashaBank\Enums\Result;
use Sarkhanrasimoghlu\PashaBank\Enums\ResultCode;

final readonly class DmsCaptureResponse
{
    public function __construct(
        public ?Result $result,
        public ?ResultCode $resultCode = null,
        public ?string $rrn = null,
        public ?string $approvalCode = null,
        public array $rawResponse = [],
    ) {}

    public function isSuccessful(): bool
    {
        return $this->result === Result::OK
            && $this->resultCode === ResultCode::Approved;
    }

    public static function fromApiResponse(array $response): self
    {
        return new self(
            result: Result::tryFrom(trim($response['RESULT'] ?? '')),
            resultCode: ResultCode::tryFrom(trim($response['RESULT_CODE'] ?? '')),
            rrn: !empty($response['RRN']) ? trim($response['RRN']) : null,
            approvalCode: !empty($response['APPROVAL_CODE']) ? trim($response['APPROVAL_CODE']) : null,
            rawResponse: $response,
        );
    }
}
