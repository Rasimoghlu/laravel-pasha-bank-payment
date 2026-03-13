<?php

namespace Sarkhanrasimoghlu\PashaBank\Listeners;

use Illuminate\Support\Facades\DB;
use Sarkhanrasimoghlu\PashaBank\Events\PaymentCreated;

class SaveTransactionListener
{
    public function handle(PaymentCreated $event): void
    {
        DB::table('pasha_bank_transactions')->insert([
            'transaction_id' => $event->transactionId,
            'order_id' => $event->orderId,
            'amount' => $event->amount,
            'currency' => $event->currency,
            'status' => $event->status,
            'message_type' => $event->messageType,
            'redirect_url' => $event->redirectUrl,
            'raw_response' => json_encode($event->rawResponse),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
