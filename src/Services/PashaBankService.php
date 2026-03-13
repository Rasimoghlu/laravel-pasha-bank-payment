<?php

namespace Sarkhanrasimoghlu\PashaBank\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Sarkhanrasimoghlu\PashaBank\Contracts\ConfigurationInterface;
use Sarkhanrasimoghlu\PashaBank\Contracts\HttpClientInterface;
use Sarkhanrasimoghlu\PashaBank\Contracts\PashaBankServiceInterface;
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
use Sarkhanrasimoghlu\PashaBank\Enums\Command;
use Sarkhanrasimoghlu\PashaBank\Enums\MessageType;
use Sarkhanrasimoghlu\PashaBank\Enums\Result;
use Sarkhanrasimoghlu\PashaBank\Events\PaymentCompleted;
use Sarkhanrasimoghlu\PashaBank\Events\PaymentCreated;
use Sarkhanrasimoghlu\PashaBank\Events\PaymentFailed;

class PashaBankService implements PashaBankServiceInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ConfigurationInterface $configuration,
        private readonly LoggerInterface $logger,
        private readonly ?Dispatcher $events = null,
    ) {}

    public function createPayment(PaymentRequest $request): PaymentResponse
    {
        return $this->registerTransaction($request, MessageType::SMS);
    }

    public function createDmsAuth(PaymentRequest $request): PaymentResponse
    {
        return $this->registerTransaction($request, MessageType::DMS);
    }

    public function executeDmsCapture(DmsCaptureRequest $request): DmsCaptureResponse
    {
        $params = [
            'command' => Command::DmsCapture->value,
            'trans_id' => $request->transactionId,
            'amount' => (string) $request->getAmountInMinorUnits(),
            'currency' => $request->currency->value,
            'client_ip_addr' => $request->clientIp,
            'msg_type' => MessageType::DMS->value,
            'terminal_id' => $this->configuration->getTerminalId(),
        ];

        if (!empty($request->description)) {
            $params['description'] = substr($request->description, 0, 125);
        }

        $this->logger->info('Pasha Bank: Executing DMS capture', [
            'trans_id' => $request->transactionId,
            'amount' => $request->amount,
        ]);

        $response = $this->httpClient->post($params);
        $result = DmsCaptureResponse::fromApiResponse($response);

        $this->syncTransactionStatus(
            $request->transactionId,
            $result->isSuccessful() ? 'succeeded' : 'failed',
            $response,
        );

        $this->logger->info('Pasha Bank: DMS capture result', [
            'trans_id' => $request->transactionId,
            'result' => $result->result?->value,
            'result_code' => $result->resultCode?->value,
        ]);

        return $result;
    }

    public function getTransactionResult(string $transactionId, string $clientIp): TransactionResult
    {
        $params = [
            'command' => Command::TransactionResult->value,
            'trans_id' => $transactionId,
            'client_ip_addr' => $clientIp,
        ];

        $this->logger->info('Pasha Bank: Getting transaction result', ['trans_id' => $transactionId]);

        $response = $this->httpClient->post($params);
        $result = TransactionResult::fromApiResponse($response);

        $this->syncTransactionStatus($transactionId, $result->status->value, $response, $result->cardNumber);

        if ($result->isSuccessful()) {
            $this->events?->dispatch(new PaymentCompleted(
                transactionId: $transactionId,
                status: $result->status,
                cardNumber: $result->cardNumber,
                rrn: $result->rrn,
                rawResponse: $response,
            ));
        } else {
            $this->events?->dispatch(new PaymentFailed(
                transactionId: $transactionId,
                result: $result->result?->value ?? '',
                resultCode: $result->resultCode?->value ?? '',
                rawResponse: $response,
            ));
        }

        $this->logger->info('Pasha Bank: Transaction result', [
            'trans_id' => $transactionId,
            'result' => $result->result?->value,
            'result_code' => $result->resultCode?->value,
            'status' => $result->status->value,
        ]);

        return $result;
    }

    public function reversal(ReversalRequest $request): ReversalResponse
    {
        $params = [
            'command' => Command::Reversal->value,
            'trans_id' => $request->transactionId,
        ];

        $amountInMinor = $request->getAmountInMinorUnits();
        if ($amountInMinor !== null) {
            $params['amount'] = (string) $amountInMinor;
        }

        if ($request->suspectedFraud) {
            $params['suspected_fraud'] = 'yes';
        }

        $this->logger->info('Pasha Bank: Reversing transaction', [
            'trans_id' => $request->transactionId,
            'amount' => $request->amount,
        ]);

        $response = $this->httpClient->post($params);
        $result = ReversalResponse::fromApiResponse($response);

        if ($result->isSuccessful()) {
            $this->syncTransactionStatus($request->transactionId, 'reversed', $response);
        }

        return $result;
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $params = [
            'command' => Command::Refund->value,
            'trans_id' => $request->transactionId,
        ];

        $amountInMinor = $request->getAmountInMinorUnits();
        if ($amountInMinor !== null) {
            $params['amount'] = (string) $amountInMinor;
        }

        $this->logger->info('Pasha Bank: Refunding transaction', [
            'trans_id' => $request->transactionId,
            'amount' => $request->amount,
        ]);

        $response = $this->httpClient->post($params);
        $result = RefundResponse::fromApiResponse($response);

        if ($result->isSuccessful()) {
            $this->syncTransactionStatus($request->transactionId, 'refunded', $response);
        }

        return $result;
    }

    public function endOfBusinessDay(): EndOfDayResponse
    {
        $params = [
            'command' => Command::EndOfBusinessDay->value,
        ];

        $this->logger->info('Pasha Bank: Closing business day');

        $response = $this->httpClient->post($params);
        $result = EndOfDayResponse::fromApiResponse($response);

        $this->logger->info('Pasha Bank: Business day closed', [
            'result' => $result->result?->value,
            'result_code' => $result->resultCode?->value,
        ]);

        return $result;
    }

    private function registerTransaction(PaymentRequest $request, MessageType $messageType): PaymentResponse
    {
        $command = $messageType === MessageType::SMS
            ? Command::SmsTransaction
            : Command::DmsAuthorization;

        $params = [
            'command' => $command->value,
            'amount' => (string) $request->getAmountInMinorUnits(),
            'currency' => $request->currency->value,
            'client_ip_addr' => $request->clientIp,
            'msg_type' => $messageType->value,
            'terminal_id' => $this->configuration->getTerminalId(),
        ];

        if (!empty($request->description)) {
            $params['description'] = substr($request->description, 0, 125);
        }

        $params['language'] = $request->language->value;

        $this->logger->info('Pasha Bank: Registering transaction', [
            'command' => $command->value,
            'msg_type' => $messageType->value,
            'amount' => $request->amount,
            'currency' => $request->currency->value,
            'order_id' => $request->orderId,
        ]);

        $response = $this->httpClient->post($params);

        if (!empty($response['error'])) {
            $this->logger->error('Pasha Bank: Transaction registration failed', [
                'error' => $response['error'],
                'response' => $response,
            ]);

            return PaymentResponse::failure($response);
        }

        $paymentResponse = PaymentResponse::fromApiResponse(
            $response,
            $this->configuration->getClientHandler(),
        );

        if (!empty($paymentResponse->transactionId)) {
            $this->events?->dispatch(new PaymentCreated(
                transactionId: $paymentResponse->transactionId,
                orderId: $request->orderId,
                amount: $request->amount,
                currency: $request->currency->value,
                status: 'pending',
                messageType: $messageType->value,
                redirectUrl: $paymentResponse->redirectUrl,
                rawResponse: $response,
            ));
        }

        $this->logger->info('Pasha Bank: Transaction registered', [
            'transaction_id' => $paymentResponse->transactionId,
            'redirect_url' => $paymentResponse->redirectUrl,
        ]);

        return $paymentResponse;
    }

    private function syncTransactionStatus(
        string $transactionId,
        string $status,
        array $response,
        ?string $cardNumber = null,
    ): void {
        try {
            DB::transaction(function () use ($transactionId, $status, $response, $cardNumber) {
                $transaction = DB::table('pasha_bank_transactions')
                    ->where('transaction_id', $transactionId)
                    ->lockForUpdate()
                    ->first();

                if (!$transaction) {
                    return;
                }

                $terminalStatuses = ['succeeded', 'reversed', 'refunded'];
                if (in_array($transaction->status, $terminalStatuses, true)) {
                    return;
                }

                $update = [
                    'status' => $status,
                    'result' => $response['RESULT'] ?? null,
                    'result_code' => $response['RESULT_CODE'] ?? null,
                    'rrn' => $response['RRN'] ?? $transaction->rrn,
                    'approval_code' => $response['APPROVAL_CODE'] ?? $transaction->approval_code,
                    'raw_response' => json_encode($response),
                    'updated_at' => now(),
                ];

                if ($cardNumber) {
                    $update['card_number'] = $cardNumber;
                }

                if ($status === 'succeeded') {
                    $update['paid_at'] = now();
                }

                DB::table('pasha_bank_transactions')
                    ->where('transaction_id', $transactionId)
                    ->update($update);
            });
        } catch (\Throwable $e) {
            $this->logger->error('Pasha Bank: Failed to sync transaction status', [
                'trans_id' => $transactionId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
