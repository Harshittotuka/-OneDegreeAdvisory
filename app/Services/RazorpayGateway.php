<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RazorpayGateway
{
    public function configured(): bool
    {
        return $this->keyId() !== '' && $this->keySecret() !== '';
    }

    public function keyId(): string
    {
        return trim((string) config('services.razorpay.key_id'));
    }

    public function createOrder(array $payload): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Razorpay is not configured.');
        }

        $response = $this->client()->post('/orders', $payload);
        if (! $response->successful()) {
            throw new RuntimeException('Razorpay order creation failed with status '.$response->status().'.');
        }

        $order = $response->json();
        if (! is_array($order) || empty($order['id'])) {
            throw new RuntimeException('Razorpay returned an invalid order response.');
        }

        return $order;
    }

    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $this->keySecret());

        return hash_equals($expected, $signature);
    }

    public function verifyWebhookSignature(string $body, string $signature): bool
    {
        $secret = trim((string) config('services.razorpay.webhook_secret'));
        if ($secret === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $body, $secret), $signature);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.razorpay.api_url'), '/'))
            ->withBasicAuth($this->keyId(), $this->keySecret())
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }

    private function keySecret(): string
    {
        return trim((string) config('services.razorpay.key_secret'));
    }
}
