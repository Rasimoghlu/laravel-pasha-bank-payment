<?php

namespace Sarkhanrasimoghlu\PashaBank\DataTransferObjects;

final readonly class PaymentResponse
{
    public function __construct(
        public string $transactionId,
        public string $redirectUrl,
        public array $rawResponse = [],
    ) {}

    public static function fromApiResponse(array $response, string $clientHandler): self
    {
        $transactionId = trim($response['TRANSACTION_ID'] ?? '');

        if (empty($transactionId)) {
            return self::failure($response);
        }

        $redirectUrl = $clientHandler . '?trans_id=' . urlencode($transactionId);

        return new self(
            transactionId: $transactionId,
            redirectUrl: $redirectUrl,
            rawResponse: $response,
        );
    }

    public static function failure(array $rawResponse = []): self
    {
        return new self(
            transactionId: '',
            redirectUrl: '',
            rawResponse: $rawResponse,
        );
    }
}
