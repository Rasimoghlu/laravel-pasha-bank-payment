<?php

namespace Sarkhanrasimoghlu\PashaBank\DataTransferObjects;

use Sarkhanrasimoghlu\PashaBank\Enums\Result;
use Sarkhanrasimoghlu\PashaBank\Enums\ResultCode;
use Sarkhanrasimoghlu\PashaBank\Enums\ThreeDSecureStatus;
use Sarkhanrasimoghlu\PashaBank\Enums\TransactionStatus;

final readonly class TransactionResult
{
    public TransactionStatus $status;

    public function __construct(
        public ?Result $result,
        public ?ResultCode $resultCode = null,
        public ?ThreeDSecureStatus $threeDSecure = null,
        public ?string $rrn = null,
        public ?string $approvalCode = null,
        public ?string $cardNumber = null,
        public ?string $recurringPaymentId = null,
        public ?string $recurringPaymentExpiry = null,
        public array $rawResponse = [],
    ) {
        $this->status = $this->result
            ? TransactionStatus::fromResult($this->result)
            : TransactionStatus::Failed;
    }

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
            threeDSecure: ThreeDSecureStatus::tryFrom(trim($response['3DSECURE'] ?? '')),
            rrn: !empty($response['RRN']) ? trim($response['RRN']) : null,
            approvalCode: !empty($response['APPROVAL_CODE']) ? trim($response['APPROVAL_CODE']) : null,
            cardNumber: !empty($response['CARD_NUMBER']) ? trim($response['CARD_NUMBER']) : null,
            recurringPaymentId: !empty($response['RECC_PMNT_ID']) ? trim($response['RECC_PMNT_ID']) : null,
            recurringPaymentExpiry: !empty($response['RECC_PMNT_EXPIRY']) ? trim($response['RECC_PMNT_EXPIRY']) : null,
            rawResponse: $response,
        );
    }
}
