<?php

namespace Sarkhanrasimoghlu\PashaBank\Tests\Unit\DataTransferObjects;

use PHPUnit\Framework\TestCase;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\PaymentRequest;
use Sarkhanrasimoghlu\PashaBank\Enums\Currency;
use Sarkhanrasimoghlu\PashaBank\Enums\Language;
use Sarkhanrasimoghlu\PashaBank\Exceptions\InvalidPaymentException;

class PaymentRequestTest extends TestCase
{
    public function test_it_creates_payment_request(): void
    {
        $request = new PaymentRequest(
            amount: 10.50,
            currency: Currency::AZN,
            clientIp: '192.168.1.1',
            orderId: 'ORDER-001',
            description: 'Test payment',
            language: Language::AZ,
        );

        $this->assertSame(10.50, $request->amount);
        $this->assertSame(Currency::AZN, $request->currency);
        $this->assertSame('192.168.1.1', $request->clientIp);
        $this->assertSame('ORDER-001', $request->orderId);
        $this->assertSame('Test payment', $request->description);
        $this->assertSame(Language::AZ, $request->language);
    }

    public function test_it_calculates_amount_in_minor_units(): void
    {
        $request = new PaymentRequest(
            amount: 10.50,
            currency: Currency::AZN,
            clientIp: '192.168.1.1',
        );

        $this->assertSame(1050, $request->getAmountInMinorUnits());
    }

    public function test_it_handles_whole_amounts(): void
    {
        $request = new PaymentRequest(
            amount: 100.00,
            currency: Currency::AZN,
            clientIp: '192.168.1.1',
        );

        $this->assertSame(10000, $request->getAmountInMinorUnits());
    }

    public function test_it_throws_on_zero_amount(): void
    {
        $this->expectException(InvalidPaymentException::class);

        new PaymentRequest(
            amount: 0,
            currency: Currency::AZN,
            clientIp: '192.168.1.1',
        );
    }

    public function test_it_throws_on_negative_amount(): void
    {
        $this->expectException(InvalidPaymentException::class);

        new PaymentRequest(
            amount: -5.00,
            currency: Currency::AZN,
            clientIp: '192.168.1.1',
        );
    }

    public function test_it_throws_on_empty_client_ip(): void
    {
        $this->expectException(InvalidPaymentException::class);

        new PaymentRequest(
            amount: 10.00,
            currency: Currency::AZN,
            clientIp: '',
        );
    }

    public function test_it_defaults_to_az_language(): void
    {
        $request = new PaymentRequest(
            amount: 10.00,
            currency: Currency::AZN,
            clientIp: '192.168.1.1',
        );

        $this->assertSame(Language::AZ, $request->language);
    }
}
