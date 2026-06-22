<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceiptTeamMail;
use App\Mail\PaymentThankYouMail;
use App\Models\PaymentAttempt;
use App\Services\PaymentBlockResolver;
use App\Services\RazorpayGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentBlockResolver $blocks,
        private RazorpayGateway $gateway,
    ) {}

    /**
     * Direct payment: resolve the server-priced option, create a Razorpay order
     * and return the checkout payload. No OTP / approval step — the customer pays
     * straight away; the amount is still taken from the CMS block server-side and
     * the payment is verified by Razorpay signature on confirm().
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_slug' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9-]+$/'],
            'block_id' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9-]+$/'],
            'option_index' => ['required', 'integer', 'min:0', 'max:30'],
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\s]{7,40}$/'],
        ]);

        if (! $this->gateway->configured()) {
            return response()->json(['message' => 'Online payment is not configured yet.'], 503);
        }

        $resolved = $this->blocks->resolve(
            $validated['page_slug'],
            $validated['block_id'],
            (int) $validated['option_index'],
        );
        if ($resolved === null) {
            return response()->json(['message' => 'That payment option is unavailable. Refresh the page and try again.'], 422);
        }

        $token = Str::random(64);

        $attempt = PaymentAttempt::create([
            'request_token' => $token,
            'session_hash' => $this->sessionHash($request),
            'page_slug' => $validated['page_slug'],
            'block_id' => $validated['block_id'],
            'option_index' => (int) $validated['option_index'],
            'item_name' => $resolved['item_name'],
            'amount' => $resolved['amount'],
            'currency' => $resolved['currency'],
            'theme_color' => $resolved['theme_color'],
            'customer_name' => trim($validated['name']),
            'customer_email' => strtolower(trim($validated['email'])),
            'customer_phone' => trim((string) ($validated['phone'] ?? '')) ?: null,
            'status' => 'order_creating',
        ]);

        try {
            $order = $this->gateway->createOrder([
                'amount' => $attempt->amount,
                'currency' => $attempt->currency,
                'receipt' => 'oda_'.substr($token, 0, 32),
                'notes' => [
                    'page' => $attempt->page_slug,
                    'block' => $attempt->block_id,
                    'item' => $attempt->item_name,
                    'customer_email' => $attempt->customer_email,
                ],
            ]);
        } catch (Throwable $e) {
            report($e);
            $attempt->update(['status' => 'order_failed', 'failure_reason' => 'Razorpay order creation failed.']);

            return response()->json(['message' => 'The payment could not be started. Please try again shortly.'], 503);
        }

        $attempt->update([
            'razorpay_order_id' => (string) $order['id'],
            'status' => 'order_created',
            'failure_reason' => null,
        ]);

        return response()->json([
            'token' => $token,
            'checkout' => $this->checkoutPayload($attempt->fresh()),
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'razorpay_payment_id' => ['required', 'string', 'max:100', 'regex:/^pay_[A-Za-z0-9]+$/'],
            'razorpay_order_id' => ['required', 'string', 'max:100', 'regex:/^order_[A-Za-z0-9]+$/'],
            'razorpay_signature' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/i'],
        ]);

        $attempt = $this->attemptForSession($request, $validated['token']);
        if ($attempt === null) {
            return response()->json(['message' => 'The payment request could not be verified.'], 422);
        }
        if ($attempt->status === 'paid'
            && hash_equals((string) $attempt->razorpay_order_id, $validated['razorpay_order_id'])
            && hash_equals((string) $attempt->razorpay_payment_id, $validated['razorpay_payment_id'])) {
            return $this->paymentSuccessResponse($attempt);
        }
        if ($attempt->status !== 'order_created') {
            return response()->json(['message' => 'The payment request could not be verified.'], 422);
        }
        if (! hash_equals((string) $attempt->razorpay_order_id, $validated['razorpay_order_id'])) {
            return response()->json(['message' => 'The payment order does not match.'], 422);
        }
        if (! $this->gateway->verifyPaymentSignature(
            (string) $attempt->razorpay_order_id,
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature'],
        )) {
            $attempt->update(['failure_reason' => 'Invalid payment signature']);

            return response()->json(['message' => 'Razorpay signature verification failed.'], 422);
        }

        $attempt->update([
            'razorpay_payment_id' => $validated['razorpay_payment_id'],
            'status' => 'paid',
            'paid_at' => now(),
            'failure_reason' => null,
        ]);

        // First (and only) transition to paid for this attempt — notify now.
        $this->sendPaymentEmails($attempt->fresh());

        return $this->paymentSuccessResponse($attempt);
    }

    private function paymentSuccessResponse(PaymentAttempt $attempt): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'message' => 'Payment verified successfully. Admissions will contact you with the next steps.',
            'payment_id' => $attempt->razorpay_payment_id,
        ]);
    }

    /**
     * On a confirmed payment, notify the admissions team (with full details) and
     * thank the customer. Sent synchronously (no queue). A mail failure is logged
     * but never fails the payment — the money is already captured.
     */
    private function sendPaymentEmails(PaymentAttempt $attempt): void
    {
        try {
            $mailer = config('site.forms.contact.mailer') ?: config('mail.default');

            $team = array_values(array_filter((array) config('site.payment_notify')));
            if ($team !== []) {
                Mail::mailer($mailer)->to($team)->send(new PaymentReceiptTeamMail($attempt));
            }

            if ($attempt->customer_email) {
                Mail::mailer($mailer)->to($attempt->customer_email)->send(new PaymentThankYouMail($attempt));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        $body = $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature', '');
        if (! $this->gateway->verifyWebhookSignature($body, $signature)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        $payload = json_decode($body, true);
        if (! is_array($payload)) {
            return response()->json(['message' => 'Invalid payload.'], 422);
        }

        $event = (string) ($payload['event'] ?? '');
        $payment = $payload['payload']['payment']['entity'] ?? [];
        $order = $payload['payload']['order']['entity'] ?? [];
        $orderId = (string) ($payment['order_id'] ?? $order['id'] ?? '');
        $paymentId = (string) ($payment['id'] ?? '');

        if ($orderId !== '') {
            $attempt = PaymentAttempt::where('razorpay_order_id', $orderId)->first();
            if ($attempt && in_array($event, ['payment.captured', 'order.paid'], true)) {
                $alreadyPaid = $attempt->status === 'paid';
                $attempt->update([
                    'razorpay_payment_id' => $paymentId ?: $attempt->razorpay_payment_id,
                    'status' => 'paid',
                    'paid_at' => $attempt->paid_at ?: now(),
                    'failure_reason' => null,
                ]);
                // Only notify if confirm() didn't already send for this payment.
                if (! $alreadyPaid) {
                    $this->sendPaymentEmails($attempt->fresh());
                }
            } elseif ($attempt && $event === 'payment.failed') {
                $attempt->update([
                    'status' => 'payment_failed',
                    'failure_reason' => mb_substr((string) ($payment['error_description'] ?? 'Razorpay reported a failed payment.'), 0, 500),
                ]);
            }
        }

        return response()->json(['ok' => true]);
    }

    private function checkoutPayload(PaymentAttempt $attempt): array
    {
        return [
            'key' => $this->gateway->keyId(),
            'amount' => $attempt->amount,
            'currency' => $attempt->currency,
            'order_id' => $attempt->razorpay_order_id,
            'name' => config('site.name'),
            'description' => $attempt->item_name,
            'theme_color' => $attempt->theme_color ?: '#F05A28',
            'prefill' => [
                'name' => $attempt->customer_name,
                'email' => $attempt->customer_email,
                'contact' => $attempt->customer_phone,
            ],
        ];
    }

    private function attemptForSession(Request $request, string $token): ?PaymentAttempt
    {
        return PaymentAttempt::where('request_token', $token)
            ->where('session_hash', $this->sessionHash($request))
            ->first();
    }

    private function sessionHash(Request $request): string
    {
        return hash('sha256', (string) $request->session()->getId());
    }
}
