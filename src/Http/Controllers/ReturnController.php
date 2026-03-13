<?php

namespace Sarkhanrasimoghlu\PashaBank\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Sarkhanrasimoghlu\PashaBank\Contracts\ConfigurationInterface;
use Sarkhanrasimoghlu\PashaBank\Contracts\PashaBankServiceInterface;

class ReturnController extends Controller
{
    /**
     * Handle client return from Pasha Bank after card entry.
     *
     * The bank redirects the client here via POST with trans_id.
     * We MUST call command=c to get the result and complete the payment.
     * If we don't call command=c within 3 minutes, the bank auto-reverses.
     */
    public function handle(
        Request $request,
        PashaBankServiceInterface $service,
        ConfigurationInterface $configuration,
    ): RedirectResponse {
        $transactionId = $request->input('trans_id', '');
        $clientIp = $request->ip();

        if (empty($transactionId)) {
            Log::warning('Pasha Bank: Return callback with empty trans_id');

            return redirect($configuration->getErrorUrl());
        }

        $transaction = DB::table('pasha_bank_transactions')
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            Log::warning('Pasha Bank: Return callback with unknown trans_id', [
                'trans_id' => $transactionId,
            ]);

            return redirect($configuration->getErrorUrl());
        }

        try {
            $result = $service->getTransactionResult($transactionId, $clientIp);

            if ($result->isSuccessful()) {
                $successUrl = $configuration->getSuccessUrl();

                return redirect($successUrl . '?trans_id=' . urlencode($transactionId));
            }

            Log::info('Pasha Bank: Payment not successful', [
                'trans_id' => $transactionId,
                'result' => $result->result?->value,
                'result_code' => $result->resultCode?->value,
            ]);

            $errorUrl = $configuration->getErrorUrl();

            return redirect($errorUrl . '?trans_id=' . urlencode($transactionId));
        } catch (\Throwable $e) {
            Log::error('Pasha Bank: Error getting transaction result', [
                'trans_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return redirect($configuration->getErrorUrl());
        }
    }
}
