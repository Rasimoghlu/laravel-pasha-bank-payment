<?php

namespace Sarkhanrasimoghlu\PashaBank\Contracts;

use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\DmsCaptureRequest;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\DmsCaptureResponse;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\EndOfDayResponse;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\PaymentRequest;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\PaymentResponse;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\RefundRequest;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\RefundResponse;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\ReversalRequest;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\ReversalResponse;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\TransactionResult;

interface PashaBankServiceInterface
{
    /**
     * Create an SMS payment (command=v). Single-step payment.
     */
    public function createPayment(PaymentRequest $request): PaymentResponse;

    /**
     * Create a DMS authorization (command=a). Pre-auth / amount blocking.
     */
    public function createDmsAuth(PaymentRequest $request): PaymentResponse;

    /**
     * Execute DMS capture (command=t). Capture a previously authorized amount.
     */
    public function executeDmsCapture(DmsCaptureRequest $request): DmsCaptureResponse;

    /**
     * Get transaction result (command=c). MUST be called after client returns from bank.
     * Auto-reversal occurs if not called within 3 minutes.
     */
    public function getTransactionResult(string $transactionId, string $clientIp): TransactionResult;

    /**
     * Reverse a transaction (command=r). Full or partial reversal.
     */
    public function reversal(ReversalRequest $request): ReversalResponse;

    /**
     * Refund a transaction (command=k). Full or partial refund.
     */
    public function refund(RefundRequest $request): RefundResponse;

    /**
     * Close business day (command=b). Must be called daily.
     */
    public function endOfBusinessDay(): EndOfDayResponse;
}
