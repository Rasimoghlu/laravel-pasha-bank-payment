<?php

namespace Sarkhanrasimoghlu\PashaBank\DataTransferObjects;

use Sarkhanrasimoghlu\PashaBank\Enums\Result;
use Sarkhanrasimoghlu\PashaBank\Enums\ResultCode;

final readonly class RefundResponse
{
    public function __construct(
        public ?Result $result,
        public ?ResultCode $resultCode = null,
        public ?string $refundTransactionId = null,
        public array $rawResponse = [],
    ) {}

    public function isSuccessful(): bool
    {
        return $this->result === Result::OK;
    }

    public static function fromApiResponse(array $response): self
    {
        return new self(
            result: Result::tryFrom(trim($response['RESULT'] ?? '')),
            resultCode: ResultCode::tryFrom(trim($response['RESULT_CODE'] ?? '')),
            refundTransactionId: !empty($response['REFUND_TRANS_ID']) ? trim($response['REFUND_TRANS_ID']) : null,
            rawResponse: $response,
        );
    }
}
