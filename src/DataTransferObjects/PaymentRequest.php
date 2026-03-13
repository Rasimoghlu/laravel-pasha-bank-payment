<?php

namespace Sarkhanrasimoghlu\PashaBank\DataTransferObjects;

use Sarkhanrasimoghlu\PashaBank\Enums\Currency;
use Sarkhanrasimoghlu\PashaBank\Enums\Language;
use Sarkhanrasimoghlu\PashaBank\Exceptions\InvalidPaymentException;

final readonly class PaymentRequest
{
    /**
     * @param float $amount Transaction amount (e.g. 10.50)
     * @param Currency $currency ISO-4217 numeric currency code
     * @param string $clientIp Client's IP address
     * @param string $orderId Application's order identifier
     * @param string $description Purchase description (max 125 chars)
     * @param Language $language Language for card entry page
     */
    public function __construct(
        public float $amount,
        public Currency $currency,
        public string $clientIp,
        public string $orderId = '',
        public string $description = '',
        public Language $language = Language::AZ,
    ) {
        if ($this->amount <= 0) {
            throw InvalidPaymentException::invalidAmount($this->amount);
        }

        if (empty($this->clientIp)) {
            throw InvalidPaymentException::missingClientIp();
        }

        if (strlen($this->description) > 125) {
            throw InvalidPaymentException::descriptionTooLong();
        }
    }

    /**
     * Convert amount to minor currency units (cents/qepik).
     * Pasha Bank expects amount in minor units (e.g. 1050 for 10.50 AZN).
     */
    public function getAmountInMinorUnits(): int
    {
        return (int) round((float) bcmul((string) $this->amount, '100', 2));
    }
}
