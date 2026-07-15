<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CrmOtpSender
{
    public function send(string $phone, string $otp): void
    {
        $url = trim((string) config('crm.sms.webhook_url'));
        if ($url === '') {
            if (app()->isLocal() || app()->runningUnitTests() || config('crm.otp.debug')) {
                Log::info('CRM login OTP generated for local development.', ['phone' => $phone, 'otp' => $otp]);

                return;
            }

            throw new RuntimeException('CRM SMS delivery is not configured.');
        }

        $request = Http::timeout(12)->acceptJson();
        $token = trim((string) config('crm.sms.bearer_token'));
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->post($url, [
            'phone' => '+91'.$phone,
            'otp' => $otp,
            'message' => "Your One Degree CRM login OTP is {$otp}. It expires in ".config('crm.otp.ttl_minutes').' minutes.',
        ]);

        if (! $response->successful()) {
            Log::warning('CRM OTP SMS webhook failed.', ['phone' => $phone, 'status' => $response->status()]);
            throw new RuntimeException('Unable to send OTP right now. Please try again.');
        }
    }
}
