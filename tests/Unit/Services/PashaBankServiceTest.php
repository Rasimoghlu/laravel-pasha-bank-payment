<?php

namespace Sarkhanrasimoghlu\PashaBank\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Sarkhanrasimoghlu\PashaBank\Contracts\ConfigurationInterface;
use Sarkhanrasimoghlu\PashaBank\Contracts\HttpClientInterface;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\DmsCaptureRequest;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\PaymentRequest;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\RefundRequest;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\ReversalRequest;
use Sarkhanrasimoghlu\PashaBank\Enums\Currency;
use Sarkhanrasimoghlu\PashaBank\Enums\Language;
use Sarkhanrasimoghlu\PashaBank\Enums\Result;
use Sarkhanrasimoghlu\PashaBank\Enums\ResultCode;
use Sarkhanrasimoghlu\PashaBank\Enums\TransactionStatus;
use Sarkhanrasimoghlu\PashaBank\Services\PashaBankService;

class PashaBankServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private ConfigurationInterface $config;
    private PashaBankService $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->config = $this->createMock(ConfigurationInterface::class);

        $this->config->method('getTerminalId')->willReturn('TEST-TERMINAL');
        $this->config->method('getClientHandler')->willReturn('https://ecomm.pashabank.az:8463/ecomm2/ClientHandler');

        $this->service = new PashaBankService(
            httpClient: $this->httpClient,
            configuration: $this->config,
            logger: new NullLogger(),
        );
    }

    public function test_create_sms_payment(): void
    {
        $this->httpClient->method('post')->willReturn([
            'TRANSACTION_ID' => 'TwXcbhBgrIsMY0A7s982nx/pSzE=',
        ]);

        $request = new PaymentRequest(
            amount: 10.50,
            currency: Currency::AZN,
            clientIp: '192.168.1.1',
            orderId: 'ORDER-001',
            description: 'Test payment',
        );

        $response = $this->service->createPayment($request);

        $this->assertSame('TwXcbhBgrIsMY0A7s982nx/pSzE=', $response->transactionId);
        $this->assertNotEmpty($response->redirectUrl);
    }

    public function test_create_sms_payment_sends_correct_params(): void
    {
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with($this->callback(function (array $params) {
                return $params['command'] === 'v'
                    && $params['amount'] === '1050'
                    && $params['currency'] === '944'
                    && $params['client_ip_addr'] === '192.168.1.1'
                    && $params['msg_type'] === 'SMS'
                    && $params['terminal_id'] === 'TEST-TERMINAL'
                    && $params['description'] === 'Test payment'
                    && $params['language'] === 'az';
            }))
            ->willReturn(['TRANSACTION_ID' => 'test-id']);

        $request = new PaymentRequest(
            amount: 10.50,
            currency: Currency::AZN,
            clientIp: '192.168.1.1',
            description: 'Test payment',
        );

        $this->service->createPayment($request);
    }

    public function test_create_dms_auth(): void
    {
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with($this->callback(function (array $params) {
                return $params['command'] === 'a'
                    && $params['msg_type'] === 'DMS';
            }))
            ->willReturn(['TRANSACTION_ID' => 'dms-trans-id']);

        $request = new PaymentRequest(
            amount: 50.00,
            currency: Currency::AZN,
            clientIp: '192.168.1.1',
        );

        $response = $this->service->createDmsAuth($request);

        $this->assertSame('dms-trans-id', $response->transactionId);
    }

    public function test_execute_dms_capture(): void
    {
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with($this->callback(function (array $params) {
                return $params['command'] === 't'
                    && $params['trans_id'] === 'dms-trans-id'
                    && $params['amount'] === '5000'
                    && $params['currency'] === '944'
                    && $params['msg_type'] === 'DMS';
            }))
            ->willReturn([
                'RESULT' => 'OK',
                'RESULT_CODE' => '000',
                'RRN' => '123456789012',
                'APPROVAL_CODE' => '123456',
            ]);

        $request = new DmsCaptureRequest(
            transactionId: 'dms-trans-id',
            amount: 50.00,
            currency: Currency::AZN,
            clientIp: '192.168.1.1',
        );

        $response = $this->service->executeDmsCapture($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(Result::OK, $response->result);
        $this->assertSame(ResultCode::Approved, $response->resultCode);
    }

    public function test_get_transaction_result_success(): void
    {
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with($this->callback(function (array $params) {
                return $params['command'] === 'c'
                    && $params['trans_id'] === 'test-trans'
                    && $params['client_ip_addr'] === '192.168.1.1';
            }))
            ->willReturn([
                'RESULT' => 'OK',
                'RESULT_CODE' => '000',
                '3DSECURE' => 'AUTHENTICATED',
                'RRN' => '123456789012',
                'APPROVAL_CODE' => '123456',
                'CARD_NUMBER' => '4***********9999',
            ]);

        $result = $this->service->getTransactionResult('test-trans', '192.168.1.1');

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(TransactionStatus::Succeeded, $result->status);
        $this->assertSame('4***********9999', $result->cardNumber);
    }

    public function test_get_transaction_result_failed(): void
    {
        $this->httpClient->method('post')->willReturn([
            'RESULT' => 'FAILED',
            'RESULT_CODE' => '116',
        ]);

        $result = $this->service->getTransactionResult('test-trans', '192.168.1.1');

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(TransactionStatus::Failed, $result->status);
    }

    public function test_reversal(): void
    {
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with($this->callback(function (array $params) {
                return $params['command'] === 'r'
                    && $params['trans_id'] === 'test-trans';
            }))
            ->willReturn([
                'RESULT' => 'OK',
                'RESULT_CODE' => '400',
            ]);

        $request = new ReversalRequest(transactionId: 'test-trans');
        $response = $this->service->reversal($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(ResultCode::ReversalAccepted, $response->resultCode);
    }

    public function test_partial_reversal(): void
    {
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with($this->callback(function (array $params) {
                return $params['command'] === 'r'
                    && $params['amount'] === '500';
            }))
            ->willReturn([
                'RESULT' => 'OK',
                'RESULT_CODE' => '400',
            ]);

        $request = new ReversalRequest(transactionId: 'test-trans', amount: 5.00);
        $response = $this->service->reversal($request);

        $this->assertTrue($response->isSuccessful());
    }

    public function test_reversal_with_suspected_fraud(): void
    {
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with($this->callback(function (array $params) {
                return $params['suspected_fraud'] === 'yes';
            }))
            ->willReturn([
                'RESULT' => 'OK',
                'RESULT_CODE' => '400',
            ]);

        $request = new ReversalRequest(
            transactionId: 'test-trans',
            suspectedFraud: true,
        );

        $this->service->reversal($request);
    }

    public function test_refund(): void
    {
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with($this->callback(function (array $params) {
                return $params['command'] === 'k'
                    && $params['trans_id'] === 'test-trans';
            }))
            ->willReturn([
                'RESULT' => 'OK',
                'RESULT_CODE' => '000',
                'REFUND_TRANS_ID' => 'refund-trans-id',
            ]);

        $request = new RefundRequest(transactionId: 'test-trans');
        $response = $this->service->refund($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('refund-trans-id', $response->refundTransactionId);
    }

    public function test_partial_refund(): void
    {
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with($this->callback(function (array $params) {
                return $params['amount'] === '300';
            }))
            ->willReturn([
                'RESULT' => 'OK',
                'RESULT_CODE' => '000',
                'REFUND_TRANS_ID' => 'refund-id',
            ]);

        $request = new RefundRequest(transactionId: 'test-trans', amount: 3.00);
        $response = $this->service->refund($request);

        $this->assertTrue($response->isSuccessful());
    }

    public function test_end_of_business_day(): void
    {
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with($this->callback(function (array $params) {
                return $params['command'] === 'b';
            }))
            ->willReturn([
                'RESULT' => 'OK',
                'RESULT_CODE' => '500',
                'FLD_076' => '5',
                'FLD_088' => '10000',
            ]);

        $response = $this->service->endOfBusinessDay();

        $this->assertTrue($response->isSuccessful());
        $this->assertSame(ResultCode::ReconciledInBalance, $response->resultCode);
        $this->assertSame(5, $response->debitTransactions);
        $this->assertSame(10000.0, $response->debitSum);
    }

    public function test_payment_failure_returns_empty_response(): void
    {
        $this->httpClient->method('post')->willReturn([
            'error' => 'some error message',
        ]);

        $request = new PaymentRequest(
            amount: 10.00,
            currency: Currency::AZN,
            clientIp: '192.168.1.1',
        );

        $response = $this->service->createPayment($request);

        $this->assertSame('', $response->transactionId);
        $this->assertSame('', $response->redirectUrl);
    }
}
