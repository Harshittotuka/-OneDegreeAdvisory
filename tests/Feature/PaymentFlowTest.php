<?php

namespace Tests\Feature;

use App\Models\CrmLead;
use App\Models\PaymentAttempt;
use App\Services\PaymentBlockResolver;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The PHP SQLite PDO driver is not installed.');
        }

        Artisan::call('migrate:fresh', ['--force' => true]);

        config()->set('services.razorpay.key_id', 'rzp_test_public123');
        config()->set('services.razorpay.key_secret', 'test_secret_456');
        config()->set('services.razorpay.webhook_secret', 'webhook_secret_789');
        $this->withCredentials()
            ->withCookie((string) config('session.cookie'), str_repeat('s', 40));
    }

    public function test_direct_checkout_creates_a_server_priced_order_and_verifies_payment(): void
    {
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_test123',
                'amount' => 6_999_900,
                'currency' => 'INR',
            ], 200),
        ]);

        $this->mock(PaymentBlockResolver::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resolve')
                ->once()
                ->with('europe', 'europe-payment', 1)
                ->andReturn([
                    'item_name' => 'Achiever',
                    'amount' => 6_999_900,
                    'currency' => 'INR',
                    'theme_color' => '#2B1FA8',
                ]);
        });

        $order = $this->postJson(route('payments.order'), [
            'page_slug' => 'europe',
            'block_id' => 'europe-payment',
            'option_index' => 1,
            'name' => 'Test Student',
            'email' => 'student@example.test',
            'phone' => '+91 9876543210',
        ])->assertOk();

        $token = (string) $order->json('token');
        $this->assertSame(64, strlen($token));

        $order->assertJsonPath('checkout.key', 'rzp_test_public123')
            ->assertJsonPath('checkout.amount', 6_999_900)
            ->assertJsonPath('checkout.order_id', 'order_test123')
            ->assertJsonPath('checkout.theme_color', '#2B1FA8');

        $this->assertDatabaseHas('payment_attempts', [
            'request_token' => $token,
            'amount' => 6_999_900,
            'theme_color' => '#2B1FA8',
            'razorpay_order_id' => 'order_test123',
            'status' => 'order_created',
        ]);

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->url() === 'https://api.razorpay.com/v1/orders'
                && $request['amount'] === 6_999_900
                && $request['currency'] === 'INR';
        });

        $paymentId = 'pay_test123';
        $signature = hash_hmac('sha256', 'order_test123|'.$paymentId, 'test_secret_456');

        $this->postJson(route('payments.confirm'), [
            'token' => $token,
            'razorpay_payment_id' => $paymentId,
            'razorpay_order_id' => 'order_test123',
            'razorpay_signature' => str_repeat('0', 64),
        ])->assertUnprocessable()->assertJsonPath('message', 'Razorpay signature verification failed.');

        $this->postJson(route('payments.confirm'), [
            'token' => $token,
            'razorpay_payment_id' => $paymentId,
            'razorpay_order_id' => 'order_test123',
            'razorpay_signature' => $signature,
        ])->assertOk()->assertJsonPath('payment_id', $paymentId);

        $this->assertDatabaseHas('payment_attempts', [
            'request_token' => $token,
            'razorpay_order_id' => 'order_test123',
            'razorpay_payment_id' => $paymentId,
            'status' => 'paid',
        ]);

        // Idempotent: replaying the same verified confirmation stays OK.
        $this->postJson(route('payments.confirm'), [
            'token' => $token,
            'razorpay_payment_id' => $paymentId,
            'razorpay_order_id' => 'order_test123',
            'razorpay_signature' => $signature,
        ])->assertOk()->assertJsonPath('payment_id', $paymentId);
    }

    public function test_webhook_requires_a_valid_signature_and_marks_captured_payment_paid(): void
    {
        $attempt = PaymentAttempt::create([
            'request_token' => str_repeat('a', 64),
            'session_hash' => str_repeat('b', 64),
            'page_slug' => 'europe',
            'block_id' => 'europe-payment',
            'option_index' => 0,
            'item_name' => 'Explorer',
            'amount' => 5_499_900,
            'currency' => 'INR',
            'theme_color' => '#F05A28',
            'customer_name' => 'Test Student',
            'customer_email' => 'student@example.test',
            'razorpay_order_id' => 'order_webhook123',
            'status' => 'order_created',
        ]);

        $body = json_encode([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_webhook123',
                'order_id' => 'order_webhook123',
            ]]],
        ], JSON_THROW_ON_ERROR);

        $this->call('POST', route('payments.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => str_repeat('0', 64),
        ], $body)->assertUnauthorized();

        $signature = hash_hmac('sha256', $body, 'webhook_secret_789');
        $this->call('POST', route('payments.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
        ], $body)->assertOk();

        $this->assertSame('paid', $attempt->fresh()->status);
        $this->assertSame('pay_webhook123', $attempt->fresh()->razorpay_payment_id);
    }

    public function test_identity_conflict_rolls_back_checkout_before_creating_a_payment_order(): void
    {
        CrmLead::query()->create([
            'lead_number' => 'OD-10001', 'name' => 'Phone Owner', 'phone' => '9876543210',
            'email' => 'phone@example.test', 'priority' => 'medium', 'status' => 'new',
        ]);
        CrmLead::query()->create([
            'lead_number' => 'OD-10002', 'name' => 'Email Owner', 'phone' => '9876543211',
            'email' => 'email@example.test', 'priority' => 'medium', 'status' => 'new',
        ]);
        $this->mock(PaymentBlockResolver::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resolve')->once()->andReturn([
                'item_name' => 'Explorer', 'amount' => 5_499_900, 'currency' => 'INR', 'theme_color' => '#F05A28',
            ]);
        });
        Http::fake();

        $this->postJson(route('payments.order'), [
            'page_slug' => 'europe', 'block_id' => 'europe-payment', 'option_index' => 0,
            'name' => 'Conflict', 'phone' => '9876543210', 'email' => 'email@example.test',
        ])->assertUnprocessable()->assertJsonValidationErrors('contact');

        $this->assertDatabaseCount('payment_attempts', 0);
        Http::assertNothingSent();
    }
}
