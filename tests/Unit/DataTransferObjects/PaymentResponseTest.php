<?php

namespace Sarkhanrasimoghlu\PashaBank\Tests\Unit\DataTransferObjects;

use PHPUnit\Framework\TestCase;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\PaymentResponse;

class PaymentResponseTest extends TestCase
{
    public function test_it_creates_from_api_response(): void
    {
        $response = ['TRANSACTION_ID' => 'TwXcbhBgrIsMY0A7s982nx/pSzE='];
        $clientHandler = 'https://ecomm.pashabank.az:8463/ecomm2/ClientHandler';

        $result = PaymentResponse::fromApiResponse($response, $clientHandler);

        $this->assertSame('TwXcbhBgrIsMY0A7s982nx/pSzE=', $result->transactionId);
        $this->assertStringContains('trans_id=', $result->redirectUrl);
        $this->assertStringStartsWith($clientHandler, $result->redirectUrl);
    }

    public function test_it_url_encodes_transaction_id(): void
    {
        $response = ['TRANSACTION_ID' => 'abc/def+ghi='];
        $clientHandler = 'https://example.com/handler';

        $result = PaymentResponse::fromApiResponse($response, $clientHandler);

        $this->assertStringContains(urlencode('abc/def+ghi='), $result->redirectUrl);
    }

    public function test_failure_returns_empty_response(): void
    {
        $result = PaymentResponse::failure(['error' => 'something']);

        $this->assertSame('', $result->transactionId);
        $this->assertSame('', $result->redirectUrl);
        $this->assertSame(['error' => 'something'], $result->rawResponse);
    }

    public function test_empty_transaction_id_returns_failure(): void
    {
        $response = ['TRANSACTION_ID' => ''];
        $clientHandler = 'https://example.com/handler';

        $result = PaymentResponse::fromApiResponse($response, $clientHandler);

        $this->assertSame('', $result->transactionId);
        $this->assertSame('', $result->redirectUrl);
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'",
        );
    }
}
