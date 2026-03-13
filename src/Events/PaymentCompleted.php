<?php

namespace Sarkhanrasimoghlu\PashaBank\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Sarkhanrasimoghlu\PashaBank\Enums\TransactionStatus;

class PaymentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $transactionId,
        public readonly TransactionStatus $status,
        public readonly ?string $cardNumber = null,
        public readonly ?string $rrn = null,
        public readonly array $rawResponse = [],
    ) {}
}
