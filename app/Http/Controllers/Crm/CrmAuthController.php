<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmOtpCode;
use App\Models\CrmUser;
use App\Services\CrmAuditLogger;
use App\Services\CrmOtpSender;
use App\Services\CrmSuperAdminSync;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CrmAuthController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('crm_user_id')) {
            return redirect()->route('crm.dashboard');
        }

        return view('crm.login');
    }

    public function requestOtp(Request $request, CrmOtpSender $sender, CrmSuperAdminSync $superAdmins): RedirectResponse
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:190']]);
        $identifier = trim($data['login']);
        $superAdmins->sync();

        if (str_contains($identifier, '@')) {
            $user = CrmUser::query()->whereRaw('LOWER(email) = ?', [strtolower($identifier)])->where('is_active', true)->first();
            if (! $user) {
                return back()->withErrors(['login' => 'This email address is not registered for CRM access.'])->withInput();
            }
            $phone = $user->phone;
        } else {
            $phone = $this->normalisePhone($identifier);
            $user = CrmUser::query()->where('phone', $phone)->where('is_active', true)->first();
            if (! $user) {
                return back()->withErrors(['login' => 'This number is not registered for CRM access.'])->withInput();
            }
        }

        CrmOtpCode::query()->where('crm_user_id', $user->id)->whereNull('used_at')->update(['used_at' => now()]);
        $otp = (string) random_int(100000, 999999);
        $record = CrmOtpCode::query()->create([
            'crm_user_id' => $user->id,
            'code_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes((int) config('crm.otp.ttl_minutes', 5)),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        try {
            $deliveryChannels = $sender->send($user, $otp);
        } catch (RuntimeException $exception) {
            /* A master-OTP holder can still get in with their standing code, so
               a dead mailbox must not block them at the first step. */
            if (! $this->hasMasterOtp($user)) {
                $record->delete();

                return back()->withErrors(['login' => $exception->getMessage()])->withInput();
            }

            Log::warning('CRM OTP delivery failed for a master-OTP account; falling back to the standing code.', [
                'phone' => $user->phone,
                'error' => $exception->getMessage(),
            ]);
            $deliveryChannels = [];
        }

        $request->session()->put('crm_otp_user_id', $user->id);
        $request->session()->put('crm_otp_phone', $phone);
        $request->session()->put('crm_otp_delivery', $deliveryChannels);

        $response = back()->with('otp_sent', true)->with('otp_phone', $phone);
        if (app()->runningUnitTests() || config('crm.otp.debug')) {
            $response->with('debug_otp', $otp);
        }

        return $response;
    }

    public function verify(Request $request, CrmAuditLogger $auditLogger): RedirectResponse
    {
        /* The generated OTP is always six digits; the range is wider only so a
           master code of a different length still reaches the check below
           instead of being rejected as a malformed field. */
        $data = $request->validate(['otp' => ['required', 'digits_between:6,12']]);
        $userId = $request->session()->get('crm_otp_user_id');

        if ($userId && $this->isMasterOtp((string) $data['otp'], (int) $userId)) {
            return $this->signIn($request, $auditLogger, (int) $userId, true);
        }

        $record = $userId ? CrmOtpCode::query()->where('crm_user_id', $userId)->whereNull('used_at')->latest()->first() : null;

        if (! $record || $record->expires_at->isPast()) {
            return back()->withErrors(['otp' => 'This OTP has expired. Request a new one.'])->with('otp_sent', true);
        }

        if ($record->attempts >= (int) config('crm.otp.max_attempts', 5)) {
            return back()->withErrors(['otp' => 'Too many incorrect attempts. Request a new OTP.'])->with('otp_sent', true);
        }

        if (! Hash::check($data['otp'], $record->code_hash)) {
            $record->increment('attempts');

            return back()->withErrors(['otp' => 'Incorrect OTP. Please try again.'])->with('otp_sent', true);
        }

        return $this->signIn($request, $auditLogger, (int) $userId, false);
    }

    /**
     * Complete a verified login: retire any outstanding codes, start the CRM
     * session, and write the audit trail.
     */
    private function signIn(Request $request, CrmAuditLogger $auditLogger, int $userId, bool $usedMasterOtp): RedirectResponse
    {
        $user = CrmUser::query()->whereKey($userId)->where('is_active', true)->first();
        if (! $user) {
            return redirect()->route('crm.login')->withErrors(['login' => 'Your CRM access is inactive.']);
        }

        CrmOtpCode::query()->where('crm_user_id', $user->id)->whereNull('used_at')->update(['used_at' => now()]);
        $user->update(['last_login_at' => now()]);
        $request->session()->regenerate();
        $request->session()->put('crm_user_id', $user->id);
        $request->session()->forget(['crm_otp_user_id', 'crm_otp_phone', 'crm_otp_delivery']);

        $auditLogger->record(
            $request,
            $user,
            'crm_login',
            $usedMasterOtp ? 'Signed in to the CRM with the master OTP.' : 'Signed in to the CRM.',
            [
                'subject_type' => 'authentication',
                'subject_id' => $user->id,
                'subject_label' => $user->name,
            ]
        );

        return redirect()->intended(route('crm.dashboard'));
    }

    /** Does the submitted code match the standing master OTP for this account? */
    private function isMasterOtp(string $submitted, int $userId): bool
    {
        $code = (string) config('crm.otp.master.code');
        if ($code === '' || ! hash_equals($code, $submitted)) {
            return false;
        }

        $user = CrmUser::query()->whereKey($userId)->where('is_active', true)->first();

        return $user !== null && $this->hasMasterOtp($user);
    }

    /** Is this account allowed to sign in with the standing master OTP? */
    private function hasMasterOtp(CrmUser $user): bool
    {
        if ((string) config('crm.otp.master.code') === '') {
            return false;
        }

        $allowed = (array) config('crm.otp.master.emails', []);

        return $allowed !== [] && in_array(strtolower(trim((string) $user->email)), $allowed, true);
    }

    public function logout(Request $request, CrmAuditLogger $auditLogger): RedirectResponse
    {
        /** @var CrmUser|null $user */
        $user = $request->attributes->get('crm_user');
        if ($user) {
            $auditLogger->record($request, $user, 'crm_logout', 'Signed out of the CRM.', [
                'subject_type' => 'authentication',
                'subject_id' => $user->id,
                'subject_label' => $user->name,
            ]);
        }

        $request->session()->forget(['crm_user_id', 'crm_otp_user_id', 'crm_otp_phone', 'crm_otp_delivery']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('crm.login');
    }

    private function normalisePhone(string $phone): string
    {
        return substr((string) preg_replace('/\D+/', '', $phone), -10);
    }
}
