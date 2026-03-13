<?php

namespace Sarkhanrasimoghlu\PashaBank\DataTransferObjects;

use Sarkhanrasimoghlu\PashaBank\Enums\Currency;
use Sarkhanrasimoghlu\PashaBank\Exceptions\InvalidPaymentException;

final readonly class DmsCaptureRequest
{
    /**
     * @param string $transactionId DMS authorization transaction ID
     * @param float $amount Amount to capture
     * @param Currency $currency Currency code
     * @param string $clientIp Client's IP address
     * @param string $description Optional description
     */
    public function __construct(
        public string $transactionId,
        public float $amount,
        public Currency $currency,
        public string $clientIp,
        public string $description = '',
    ) {
        if (empty($this->transactionId)) {
            throw InvalidPaymentException::missingTransactionId();
        }

        if ($this->amount <= 0) {
            throw InvalidPaymentException::invalidAmount($this->amount);
        }

        if (empty($this->clientIp)) {
            throw InvalidPaymentException::missingClientIp();
        }
    }

    public function getAmountInMinorUnits(): int
    {
        return (int) bcmul((string) $this->amount, '100', 0);
    }
}
