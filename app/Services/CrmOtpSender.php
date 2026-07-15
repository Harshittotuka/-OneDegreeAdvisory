<?php

namespace App\Services;

use App\Mail\CrmOtpMail;
use App\Models\CrmUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class CrmOtpSender
{
    /** @return list<string> */
    public function send(CrmUser $user, string $otp): array
    {
        if (app()->isLocal() || config('crm.otp.debug')) {
            Log::info('CRM login OTP generated for local development.', ['phone' => $user->phone, 'otp' => $otp]);

            return ['debug'];
        }

        $delivered = [];
        foreach ((array) config('crm.otp.channels', ['email', 'sms']) as $channel) {
            try {
                if ($channel === 'email') {
                    $this->sendEmail($user, $otp);
                    $delivered[] = 'email';
                } elseif ($channel === 'sms') {
                    $this->sendSms($user->phone, $otp);
                    $delivered[] = 'sms';
                }
            } catch (Throwable $exception) {
                Log::warning('CRM OTP delivery channel failed.', [
                    'channel' => $channel,
                    'phone' => $user->phone,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($delivered === []) {
            throw new RuntimeException('Unable to send OTP right now. Please contact a super admin to check your email or SMS setup.');
        }

        return array_values(array_unique($delivered));
    }

    private function sendEmail(CrmUser $user, string $otp): void
    {
        if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('No valid email is registered for this CRM user.');
        }

        Mail::mailer((string) config('crm.email.mailer', 'contact_form'))
            ->to($user->email, $user->name)
            ->send(new CrmOtpMail($user, $otp));
    }

    private function sendSms(string $phone, string $otp): void
    {
        if (config('crm.sms.driver') === 'webhook') {
            $this->sendWebhook($phone, $otp);

            return;
        }

        $authKey = trim((string) config('crm.sms.msg91.auth_key'));
        $flowId = trim((string) config('crm.sms.msg91.flow_id'));
        if ($authKey === '' || $flowId === '') {
            throw new RuntimeException('MSG91 SMS is not configured.');
        }

        $variable = trim((string) config('crm.sms.msg91.otp_variable', 'OTP')) ?: 'OTP';
        $response = Http::timeout(12)
            ->acceptJson()
            ->withHeaders(['authkey' => $authKey])
            ->post((string) config('crm.sms.msg91.endpoint'), [
                'template_id' => $flowId,
                'short_url' => '0',
                'realTimeResponse' => '1',
                'recipients' => [[
                    'mobiles' => '91'.$phone,
                    $variable => $otp,
                ]],
            ]);

        $payload = $response->json();
        if (! $response->successful() || (($payload['type'] ?? null) === 'error')) {
            Log::warning('CRM OTP MSG91 request failed.', ['phone' => $phone, 'status' => $response->status()]);
            throw new RuntimeException('Unable to send OTP by SMS right now.');
        }
    }

    private function sendWebhook(string $phone, string $otp): void
    {
        $url = trim((string) config('crm.sms.webhook_url'));
        if ($url === '') {
            throw new RuntimeException('CRM SMS webhook is not configured.');
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
            throw new RuntimeException('Unable to send OTP by SMS right now.');
        }
    }
}
