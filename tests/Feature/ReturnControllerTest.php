<?php

namespace Sarkhanrasimoghlu\PashaBank\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;
use Sarkhanrasimoghlu\PashaBank\Contracts\PashaBankServiceInterface;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\TransactionResult;
use Sarkhanrasimoghlu\PashaBank\Enums\Result;
use Sarkhanrasimoghlu\PashaBank\Enums\ResultCode;
use Sarkhanrasimoghlu\PashaBank\Enums\ThreeDSecureStatus;
use Sarkhanrasimoghlu\PashaBank\Enums\TransactionStatus;
use Sarkhanrasimoghlu\PashaBank\Events\PaymentCompleted;
use Sarkhanrasimoghlu\PashaBank\Events\PaymentFailed;
use Sarkhanrasimoghlu\PashaBank\PashaBankServiceProvider;

class ReturnControllerTest extends TestCase
{
    private PashaBankServiceInterface $service;

    protected function getPackageProviders($app): array
    {
        return [PashaBankServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('pasha-bank.merchant_handler', 'https://ecomm.test:18443/ecomm2/MerchantHandler');
        $app['config']->set('pasha-bank.client_handler', 'https://ecomm.test:8463/ecomm2/ClientHandler');
        $app['config']->set('pasha-bank.terminal_id', 'TEST-TERMINAL');
        $app['config']->set('pasha-bank.certificate', '/tmp/test-cert.p12');
        $app['config']->set('pasha-bank.ssl_verify', true);
        $app['config']->set('pasha-bank.success_url', 'https://example.com/success');
        $app['config']->set('pasha-bank.error_url', 'https://example.com/error');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->service = $this->createMock(PashaBankServiceInterface::class);
        $this->app->instance(PashaBankServiceInterface::class, $this->service);
    }

    public function test_successful_payment_redirects_to_success_url(): void
    {
        Event::fake();

        DB::table('pasha_bank_transactions')->insert([
            'transaction_id' => 'test-trans-001',
            'order_id' => 'ORDER-001',
            'amount' => 10.50,
            'currency' => '944',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service->method('getTransactionResult')->willReturn(new TransactionResult(
            result: Result::OK,
            resultCode: ResultCode::Approved,
            threeDSecure: ThreeDSecureStatus::Authenticated,
            rrn: '123456789012',
            approvalCode: '123456',
            cardNumber: '4***********9999',
        ));

        $response = $this->post('/pasha-bank/return', [
            'trans_id' => 'test-trans-001',
        ]);

        $response->assertRedirect();
        $this->assertStringContains('success', $response->headers->get('Location'));
        $this->assertStringContains('trans_id=', $response->headers->get('Location'));
    }

    public function test_failed_payment_redirects_to_error_url(): void
    {
        Event::fake();

        DB::table('pasha_bank_transactions')->insert([
            'transaction_id' => 'test-trans-002',
            'order_id' => 'ORDER-002',
            'amount' => 20.00,
            'currency' => '944',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service->method('getTransactionResult')->willReturn(new TransactionResult(
            result: Result::Failed,
            resultCode: ResultCode::DeclineInsufficientFunds,
        ));

        $response = $this->post('/pasha-bank/return', [
            'trans_id' => 'test-trans-002',
        ]);

        $response->assertRedirect();
        $this->assertStringContains('error', $response->headers->get('Location'));
    }

    public function test_empty_trans_id_redirects_to_error(): void
    {
        $response = $this->post('/pasha-bank/return', []);

        $response->assertRedirect();
        $this->assertStringContains('error', $response->headers->get('Location'));
    }

    public function test_exception_redirects_to_error(): void
    {
        DB::table('pasha_bank_transactions')->insert([
            'transaction_id' => 'test-trans-003',
            'order_id' => 'ORDER-003',
            'amount' => 15.00,
            'currency' => '944',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service->method('getTransactionResult')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $response = $this->post('/pasha-bank/return', [
            'trans_id' => 'test-trans-003',
        ]);

        $response->assertRedirect();
        $this->assertStringContains('error', $response->headers->get('Location'));
    }

    public function test_autoreversed_redirects_to_error(): void
    {
        Event::fake();

        DB::table('pasha_bank_transactions')->insert([
            'transaction_id' => 'test-trans-004',
            'order_id' => 'ORDER-004',
            'amount' => 25.00,
            'currency' => '944',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service->method('getTransactionResult')->willReturn(new TransactionResult(
            result: Result::AutoReversed,
            resultCode: ResultCode::Approved,
        ));

        $response = $this->post('/pasha-bank/return', [
            'trans_id' => 'test-trans-004',
        ]);

        $response->assertRedirect();
        $this->assertStringContains('error', $response->headers->get('Location'));
    }

    public function test_unknown_trans_id_redirects_to_error(): void
    {
        $response = $this->post('/pasha-bank/return', [
            'trans_id' => 'non-existent-id',
        ]);

        $response->assertRedirect();
        $this->assertStringContains('error', $response->headers->get('Location'));
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'",
        );
    }
}
