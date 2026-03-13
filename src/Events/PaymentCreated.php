<?php

namespace Sarkhanrasimoghlu\PashaBank\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $transactionId,
        public readonly string $orderId,
        public readonly float $amount,
        public readonly string $currency = '',
        public readonly string $status = 'pending',
        public readonly string $messageType = 'SMS',
        public readonly string $redirectUrl = '',
        public readonly array $rawResponse = [],
    ) {}
}
