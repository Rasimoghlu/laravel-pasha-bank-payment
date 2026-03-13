<?php

namespace Sarkhanrasimoghlu\PashaBank\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $transactionId,
        public readonly string $result = '',
        public readonly string $resultCode = '',
        public readonly array $rawResponse = [],
    ) {}
}
