<?php

namespace Sarkhanrasimoghlu\PashaBank\Tests\Unit\DataTransferObjects;

use PHPUnit\Framework\TestCase;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\RefundRequest;
use Sarkhanrasimoghlu\PashaBank\Exceptions\InvalidPaymentException;

class RefundRequestTest extends TestCase
{
    public function test_it_creates_full_refund(): void
    {
        $request = new RefundRequest(transactionId: 'TwXcbhBgrIsMY0A7s982nx/pSzE=');

        $this->assertSame('TwXcbhBgrIsMY0A7s982nx/pSzE=', $request->transactionId);
        $this->assertNull($request->amount);
        $this->assertNull($request->getAmountInMinorUnits());
    }

    public function test_it_creates_partial_refund(): void
    {
        $request = new RefundRequest(
            transactionId: 'TwXcbhBgrIsMY0A7s982nx/pSzE=',
            amount: 5.50,
        );

        $this->assertSame(5.50, $request->amount);
        $this->assertSame(550, $request->getAmountInMinorUnits());
    }

    public function test_it_throws_on_empty_transaction_id(): void
    {
        $this->expectException(InvalidPaymentException::class);

        new RefundRequest(transactionId: '');
    }

    public function test_it_throws_on_negative_amount(): void
    {
        $this->expectException(InvalidPaymentException::class);

        new RefundRequest(transactionId: 'test', amount: -5.00);
    }
}
