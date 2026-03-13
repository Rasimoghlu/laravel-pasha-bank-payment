# Laravel Pasha Bank Payment

A Laravel package for integrating with the **Pasha Bank ECOMM** payment system. Supports SMS payments, DMS (pre-authorization + capture), reversals, refunds, and end-of-business-day settlement out of the box.

## Requirements

- PHP 8.3+
- Laravel 12.0+
- Guzzle 7.8+
- SSL client certificate (provided by Pasha Bank)

## Installation

```bash
composer require sarkhanrasimoghlu/laravel-pasha-bank-payment
```

The service provider is auto-discovered. Publish the config and migration:

```bash
php artisan vendor:publish --tag=pasha-bank-config
php artisan vendor:publish --tag=pasha-bank-migrations
php artisan migrate
```

## Configuration

Add these to your `.env` file:

```env
PASHA_BANK_TERMINAL_ID=your-terminal-id
PASHA_BANK_CERTIFICATE=/path/to/keystore.p12
PASHA_BANK_CERTIFICATE_PASSWORD=your-cert-password
PASHA_BANK_PRIVATE_KEY=/path/to/key.pem
PASHA_BANK_CA_CERTIFICATE=/path/to/PSroot.pem
PASHA_BANK_SUCCESS_URL=https://yourapp.com/payment/success
PASHA_BANK_ERROR_URL=https://yourapp.com/payment/error
```

| Key | Required | Description |
|-----|----------|-------------|
| `TERMINAL_ID` | Yes | Terminal ID provided by the bank |
| `CERTIFICATE` | Yes | Path to `.p12` (PKCS#12) or `.pem` client certificate |
| `CERTIFICATE_PASSWORD` | No | Password for the certificate file |
| `PRIVATE_KEY` | No | Path to private key `.pem` (if using PEM format) |
| `PRIVATE_KEY_PASSWORD` | No | Password for the private key |
| `CA_CERTIFICATE` | No | Path to bank's CA certificate (`PSroot.pem`) |
| `SUCCESS_URL` | Yes | URL to redirect client after successful payment |
| `ERROR_URL` | Yes | URL to redirect client after failed payment |

Optional settings:

```env
PASHA_BANK_MERCHANT_HANDLER=https://ecomm.pashabank.az:18443/ecomm2/MerchantHandler
PASHA_BANK_CLIENT_HANDLER=https://ecomm.pashabank.az:8463/ecomm2/ClientHandler
PASHA_BANK_CURRENCY=944               # ISO-4217 numeric code (944=AZN, 840=USD, 978=EUR)
PASHA_BANK_LANGUAGE=az                 # Language for card entry page (az, en, ru)
PASHA_BANK_TIMEOUT=30                  # HTTP timeout in seconds
PASHA_BANK_SSL_VERIFY=true             # SSL certificate verification
PASHA_BANK_LOG_CHANNEL=stack           # Log channel
PASHA_BANK_LOG_LEVEL=info              # Log level
```

### SSL Certificate Setup

Pasha Bank uses mutual TLS (mTLS) with client certificates. The bank provides a ZIP archive containing:

- `certificate.<merchant_id>.pem` — Merchant certificate (PEM)
- `imakstore.ecpw.<merchant_id>.p12` — PKCS#12 keystore
- `PSroot.pem` — Bank's CA certificate

You can use either the `.p12` file directly or convert to PEM:

```bash
# Extract certificate from P12
openssl pkcs12 -in keystore.p12 -out merchant.cert.pem -clcerts -nokeys

# Extract private key from P12
openssl pkcs12 -in keystore.p12 -out merchant.key.pem -nocerts -nodes
```

## Usage

### SMS Payment (Single-Step)

The most common payment flow. Customer enters card data on the bank's page.

```php
use Sarkhanrasimoghlu\PashaBank\Contracts\PashaBankServiceInterface;
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\PaymentRequest;
use Sarkhanrasimoghlu\PashaBank\Enums\Currency;
use Sarkhanrasimoghlu\PashaBank\Enums\Language;

$service = app(PashaBankServiceInterface::class);

$request = new PaymentRequest(
    amount: 49.99,
    currency: Currency::AZN,
    clientIp: request()->ip(),
    orderId: 'ORDER-12345',
    description: 'Premium subscription',
    language: Language::AZ,
);

$response = $service->createPayment($request);

// Redirect customer to bank's card entry page
return redirect($response->redirectUrl);

// $response->transactionId  — Base64-encoded, 28 chars (save this!)
// $response->redirectUrl    — bank's ClientHandler URL with trans_id
// $response->rawResponse    — full API response
```

### DMS Payment (Two-Step: Pre-Auth + Capture)

First authorize (block the amount), then capture later.

```php
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\DmsCaptureRequest;

// Step 1: Pre-authorize (block amount on customer's card)
$response = $service->createDmsAuth($request);
return redirect($response->redirectUrl);

// Step 2: After customer returns and result is OK, capture the amount
$capture = $service->executeDmsCapture(new DmsCaptureRequest(
    transactionId: $transactionId,
    amount: 49.99,
    currency: Currency::AZN,
    clientIp: request()->ip(),
));

if ($capture->isSuccessful()) {
    // Amount captured successfully
    // $capture->rrn          — Retrieval Reference Number
    // $capture->approvalCode — 6-char approval code
}
```

### Get Transaction Result (CRITICAL)

After the bank redirects the customer back, you **MUST** call `getTransactionResult()` within 3 minutes. If not called, the bank **automatically reverses** the transaction.

The package handles this automatically via the built-in return route (`POST /pasha-bank/return`), but you can also call it manually:

```php
$result = $service->getTransactionResult($transactionId, request()->ip());

$result->isSuccessful();        // true if RESULT=OK and RESULT_CODE=000
$result->status;                // TransactionStatus::Succeeded, Failed, etc.
$result->result;                // Result::OK, Failed, Pending, AutoReversed, etc.
$result->resultCode;            // ResultCode::Approved (000), DeclineInsufficientFunds (116), etc.
$result->threeDSecure;          // ThreeDSecureStatus::Authenticated, Declined, etc.
$result->rrn;                   // "123456789012" — Retrieval Reference Number
$result->approvalCode;          // "123456"
$result->cardNumber;            // "4***********9999" (masked)
$result->recurringPaymentId;    // For recurring payments only
$result->rawResponse;           // Full response array
```

### Reversal

Full or partial reversal of a transaction.

```php
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\ReversalRequest;

// Full reversal
$response = $service->reversal(new ReversalRequest(
    transactionId: $transactionId,
));

// Partial reversal
$response = $service->reversal(new ReversalRequest(
    transactionId: $transactionId,
    amount: 15.00,
));

// Suspected fraud (full reversal only)
$response = $service->reversal(new ReversalRequest(
    transactionId: $transactionId,
    suspectedFraud: true,
));

$response->isSuccessful(); // true if RESULT=OK, RESULT_CODE=400
```

### Refund

Full or partial refund of a completed transaction.

```php
use Sarkhanrasimoghlu\PashaBank\DataTransferObjects\RefundRequest;

// Full refund
$response = $service->refund(new RefundRequest(
    transactionId: $transactionId,
));

// Partial refund
$response = $service->refund(new RefundRequest(
    transactionId: $transactionId,
    amount: 10.00,
));

$response->isSuccessful();         // true if RESULT=OK
$response->refundTransactionId;    // Refund transaction ID (can be used for reversal of refund)
```

### End of Business Day

Must be called daily to close the business day and reconcile transactions.

```php
$response = $service->endOfBusinessDay();

$response->isSuccessful();       // true if RESULT=OK
$response->resultCode;           // ReconciledInBalance (500) or ReconciledOutOfBalance (501)
$response->debitTransactions;    // Number of debit transactions
$response->debitSum;             // Sum of debit transactions
$response->creditTransactions;   // Number of credit transactions
$response->creditSum;            // Sum of credit transactions
```

Schedule it in your `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;
use Sarkhanrasimoghlu\PashaBank\Contracts\PashaBankServiceInterface;

Schedule::call(function () {
    app(PashaBankServiceInterface::class)->endOfBusinessDay();
})->dailyAt('23:59');
```

## Payment Flow

### SMS Flow (Single-Step)

```
1. Merchant → POST command=v → Bank API → TRANSACTION_ID
2. Merchant → Redirect customer → Bank's card entry page
3. Customer enters card data, completes 3D-Secure
4. Bank → Redirects customer → POST /pasha-bank/return (with trans_id)
5. Package → POST command=c → Bank API → RESULT (auto-handled)
6. Package → Redirects customer → success_url or error_url
```

### DMS Flow (Two-Step)

```
1. Merchant → POST command=a → Bank API → TRANSACTION_ID (pre-auth)
2. Merchant → Redirect customer → Bank's card entry page
3. Customer enters card data, completes 3D-Secure
4. Bank → Redirects customer back → Package calls command=c
5. Later: Merchant → POST command=t → Bank API → Capture amount
```

**Important:** If `command=c` is not called within 3 minutes after the customer returns, the bank **automatically reverses** the transaction.

## Events

The package dispatches Laravel events:

```php
use Sarkhanrasimoghlu\PashaBank\Events\PaymentCreated;
use Sarkhanrasimoghlu\PashaBank\Events\PaymentCompleted;
use Sarkhanrasimoghlu\PashaBank\Events\PaymentFailed;

// In EventServiceProvider or listener registration:
protected $listen = [
    PaymentCreated::class => [
        // Fired when createPayment() or createDmsAuth() succeeds
        // $event->transactionId, $event->orderId, $event->amount, $event->currency
    ],
    PaymentCompleted::class => [
        // Fired when command=c returns RESULT=OK
        // $event->transactionId, $event->status, $event->cardNumber, $event->rrn
    ],
    PaymentFailed::class => [
        // Fired when command=c returns RESULT != OK
        // $event->transactionId, $event->result, $event->resultCode
    ],
];
```

Example listener:

```php
class HandlePaymentCompleted
{
    public function handle(PaymentCompleted $event): void
    {
        $order = Order::where('payment_id', $event->transactionId)->first();
        $order->markAsPaid();
    }
}
```

## Authentication

The package uses **mutual TLS (mTLS)** with SSL client certificates over **TLSv1.2**. Authentication is handled automatically:

- PKCS#12 (`.p12`) and PEM formats supported
- TLSv1.2 enforced with secure cipher suites
- Certificate paths configured via `.env`
- No token management needed (unlike OAuth2)

## API Commands

| Command | Letter | Service Method | Description |
|---------|--------|----------------|-------------|
| SMS Transaction | `v` | `createPayment()` | Single-step payment |
| DMS Authorization | `a` | `createDmsAuth()` | Pre-authorize (block amount) |
| DMS Capture | `t` | `executeDmsCapture()` | Capture pre-authorized amount |
| Transaction Result | `c` | `getTransactionResult()` | Get result (auto-handled on return) |
| Reversal | `r` | `reversal()` | Full or partial reversal |
| Refund | `k` | `refund()` | Full or partial refund |
| End of Business Day | `b` | `endOfBusinessDay()` | Daily settlement |

## Transaction Statuses

| Enum Case | Value | Description |
|-----------|-------|-------------|
| `Pending` | `pending` | Transaction registered, awaiting customer |
| `Succeeded` | `succeeded` | Payment completed (RESULT=OK) |
| `Failed` | `failed` | Payment failed (RESULT=FAILED) |
| `Declined` | `declined` | Payment declined by bank |
| `Reversed` | `reversed` | Reversed by merchant |
| `AutoReversed` | `autoreversed` | Auto-reversed (command=c not called in time) |
| `Refunded` | `refunded` | Fully refunded |
| `Timeout` | `timeout` | Transaction timed out |

## Result Codes

| Code | Description |
|------|-------------|
| `000` | Approved |
| `100` | Decline (general) |
| `101` | Decline, expired card |
| `102` | Decline, suspected fraud |
| `110` | Decline, invalid amount |
| `116` | Decline, insufficient funds |
| `400` | Reversal accepted |
| `500` | Reconciled, in balance |
| `501` | Reconciled, out of balance |

See `ResultCode` enum for the full list of 26 documented codes.

## Error Handling

All exceptions extend `PashaBankException`:

```php
use Sarkhanrasimoghlu\PashaBank\Exceptions\PashaBankException;
use Sarkhanrasimoghlu\PashaBank\Exceptions\HttpException;
use Sarkhanrasimoghlu\PashaBank\Exceptions\InvalidPaymentException;
use Sarkhanrasimoghlu\PashaBank\Exceptions\InvalidConfigurationException;

try {
    $response = $service->createPayment($request);
} catch (HttpException $e) {
    // Connection failed, SSL error, server error
    $context = $e->getContext(); // ['url' => '...', 'ssl_error' => '...']
} catch (InvalidPaymentException $e) {
    // Invalid amount, missing transaction ID, missing client IP
} catch (InvalidConfigurationException $e) {
    // Missing terminal_id, certificate path, etc.
} catch (PashaBankException $e) {
    // Catch-all for any package exception
}
```

## Database

The package creates a `pasha_bank_transactions` table:

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `transaction_id` | string | Pasha Bank transaction ID (unique) |
| `order_id` | string | Your application's order ID (indexed) |
| `amount` | decimal(10,2) | Payment amount |
| `currency` | string(3) | ISO-4217 numeric currency code |
| `status` | string | Transaction status (indexed, default: `pending`) |
| `message_type` | string(3) | SMS or DMS |
| `card_number` | string | Masked card number (nullable) |
| `rrn` | string(12) | Retrieval Reference Number (nullable) |
| `approval_code` | string(6) | Approval code (nullable) |
| `result` | string | RESULT value from bank (nullable) |
| `result_code` | string(3) | RESULT_CODE from bank (nullable) |
| `redirect_url` | text | Bank's checkout URL (nullable) |
| `raw_response` | json | API response (nullable) |
| `paid_at` | timestamp | When payment succeeded (nullable) |
| `created_at` | timestamp | Record creation time |
| `updated_at` | timestamp | Last update time |

## Currency Codes

| Code | Currency |
|------|----------|
| `944` | AZN (Azerbaijani Manat) |
| `840` | USD (US Dollar) |
| `978` | EUR (Euro) |
| `826` | GBP (Pound Sterling) |

## Local Development

For local testing without a real bank certificate, set `PASHA_BANK_SSL_VERIFY=false` in your `.env`. This disables TLS 1.2 enforcement and certificate requirements, allowing you to test against a local mock server over plain HTTP:

```env
PASHA_BANK_MERCHANT_HANDLER=http://127.0.0.1:9001/ecomm2/MerchantHandler
PASHA_BANK_CLIENT_HANDLER=http://127.0.0.1:9001/ecomm2/ClientHandler
PASHA_BANK_SSL_VERIFY=false
```

**Never set `ssl_verify=false` in production.**

## Testing

```bash
./vendor/bin/phpunit
```

The package includes 48 tests covering:
- Configuration validation
- DTO construction, validation, and amount conversion
- Transaction result parsing (success, failure, timeout, auto-reversal)
- Service layer (SMS, DMS, reversal, refund, end of day)
- Return controller (success redirect, error redirect, exceptions)

## License

MIT
