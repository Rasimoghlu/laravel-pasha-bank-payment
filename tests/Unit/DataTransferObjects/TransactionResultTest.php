<?php

namespace Sarkhanrasimoghlu\PashaBank\Tests\Unit\DataTransferObjects;

use PHPUnit\Framework\TestCase;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\TransactionResult;
use Sarkhanrasimoghlu\PashaBank\Enums\Result;
use Sarkhanrasimoghlu\PashaBank\Enums\ResultCode;
use Sarkhanrasimoghlu\PashaBank\Enums\ThreeDSecureStatus;
use Sarkhanrasimoghlu\PashaBank\Enums\TransactionStatus;

class TransactionResultTest extends TestCase
{
    public function test_it_parses_successful_response(): void
    {
        $response = [
            'RESULT' => 'OK',
            'RESULT_CODE' => '000',
            '3DSECURE' => 'AUTHENTICATED',
            'RRN' => '123456789012',
            'APPROVAL_CODE' => '123456',
            'CARD_NUMBER' => '4***********9999',
        ];

        $result = TransactionResult::fromApiResponse($response);

        $this->assertSame(Result::OK, $result->result);
        $this->assertSame(ResultCode::Approved, $result->resultCode);
        $this->assertSame(ThreeDSecureStatus::Authenticated, $result->threeDSecure);
        $this->assertSame('123456789012', $result->rrn);
        $this->assertSame('123456', $result->approvalCode);
        $this->assertSame('4***********9999', $result->cardNumber);
        $this->assertTrue($result->isSuccessful());
        $this->assertSame(TransactionStatus::Succeeded, $result->status);
    }

    public function test_it_parses_failed_response(): void
    {
        $response = [
            'RESULT' => 'FAILED',
            'RESULT_CODE' => '116',
        ];

        $result = TransactionResult::fromApiResponse($response);

        $this->assertSame(Result::Failed, $result->result);
        $this->assertSame(ResultCode::DeclineInsufficientFunds, $result->resultCode);
        $this->assertFalse($result->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $result->status);
    }

    public function test_it_parses_autoreversed_response(): void
    {
        $response = [
            'RESULT' => 'AUTOREVERSED',
            'RESULT_CODE' => '000',
        ];

        $result = TransactionResult::fromApiResponse($response);

        $this->assertSame(Result::AutoReversed, $result->result);
        $this->assertFalse($result->isSuccessful());
        $this->assertSame(TransactionStatus::AutoReversed, $result->status);
    }

    public function test_it_parses_pending_response(): void
    {
        $response = [
            'RESULT' => 'PENDING',
        ];

        $result = TransactionResult::fromApiResponse($response);

        $this->assertSame(Result::Pending, $result->result);
        $this->assertFalse($result->isSuccessful());
        $this->assertSame(TransactionStatus::Pending, $result->status);
    }

    public function test_it_parses_declined_response(): void
    {
        $response = [
            'RESULT' => 'DECLINED',
            'RESULT_CODE' => '101',
        ];

        $result = TransactionResult::fromApiResponse($response);

        $this->assertSame(Result::Declined, $result->result);
        $this->assertSame(ResultCode::DeclineExpiredCard, $result->resultCode);
        $this->assertSame(TransactionStatus::Declined, $result->status);
    }

    public function test_it_handles_recurring_fields(): void
    {
        $response = [
            'RESULT' => 'OK',
            'RESULT_CODE' => '000',
            'RECC_PMNT_ID' => '1258',
            'RECC_PMNT_EXPIRY' => '1108',
            'RRN' => '123456789012',
            'APPROVAL_CODE' => '123456',
            'CARD_NUMBER' => '4***********9999',
        ];

        $result = TransactionResult::fromApiResponse($response);

        $this->assertSame('1258', $result->recurringPaymentId);
        $this->assertSame('1108', $result->recurringPaymentExpiry);
    }

    public function test_it_handles_empty_response(): void
    {
        $result = TransactionResult::fromApiResponse([]);

        $this->assertNull($result->result);
        $this->assertNull($result->resultCode);
        $this->assertNull($result->rrn);
        $this->assertFalse($result->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $result->status);
    }

    public function test_it_handles_timeout_response(): void
    {
        $response = [
            'RESULT' => 'TIMEOUT',
        ];

        $result = TransactionResult::fromApiResponse($response);

        $this->assertSame(Result::Timeout, $result->result);
        $this->assertSame(TransactionStatus::Timeout, $result->status);
    }
}
