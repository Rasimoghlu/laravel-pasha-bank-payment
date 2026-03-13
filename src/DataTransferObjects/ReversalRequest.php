<?php

namespace Sarkhanrasimoghlu\PashaBank\DataTransferObjects;

use Sarkhanrasimoghlu\PashaBank\Exceptions\InvalidPaymentException;

final readonly class ReversalRequest
{
    /**
     * @param string $transactionId Transaction ID to reverse
     * @param float|null $amount Reversal amount (null = full reversal)
     * @param bool $suspectedFraud Mark as suspected fraud (only full reversal allowed)
     */
    public function __construct(
        public string $transactionId,
        public ?float $amount = null,
        public bool $suspectedFraud = false,
    ) {
        if (empty($this->transactionId)) {
            throw InvalidPaymentException::missingTransactionId();
        }

        if ($this->amount !== null && $this->amount <= 0) {
            throw InvalidPaymentException::invalidAmount($this->amount);
        }
    }

    public function getAmountInMinorUnits(): ?int
    {
        if ($this->amount === null) {
            return null;
        }

        return (int) round($this->amount * 100);
    }
}
