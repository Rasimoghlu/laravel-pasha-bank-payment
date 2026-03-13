<?php

namespace Sarkhanrasimoghlu\PashaBank\DataTransferObjects;

use Sarkhanrasimoghlu\PashaBank\Exceptions\InvalidPaymentException;

final readonly class RefundRequest
{
    /**
     * @param string $transactionId Original transaction ID
     * @param float|null $amount Refund amount (null = full refund)
     */
    public function __construct(
        public string $transactionId,
        public ?float $amount = null,
    ) {
        if (empty($this->transactionId)) {
            throw InvalidPaymentException::missingTransactionId();
        }

        if ($this->amount !== null && $this->amount <= 0) {
            throw InvalidPaymentException::invalidAmount($this->amount);
        }
    }

    /**
     * Get amount in minor units, or null for full refund.
     */
    public function getAmountInMinorUnits(): ?int
    {
        if ($this->amount === null) {
            return null;
        }

        return (int) round($this->amount * 100);
    }
}
