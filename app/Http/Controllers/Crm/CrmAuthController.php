<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmOtpCode;
use App\Models\CrmUser;
use App\Services\CrmNotifier;
use App\Services\CrmOtpSender;
use App\Services\CrmSuperAdminSync;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        $data = $request->validate(['phone' => ['required', 'string', 'max:30']]);
        $phone = $this->normalisePhone($data['phone']);
        $superAdmins->sync();
        $user = CrmUser::query()->where('phone', $phone)->where('is_active', true)->first();

        if (! $user) {
            return back()->withErrors(['phone' => 'This number is not registered for CRM access.'])->withInput();
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
            $record->delete();

            return back()->withErrors(['phone' => $exception->getMessage()])->withInput();
        }

        $request->session()->put('crm_otp_user_id', $user->id);
        $request->session()->put('crm_otp_phone', $phone);
        $request->session()->put('crm_otp_delivery', $deliveryChannels);

        $response = back()->with('otp_sent', true)->with('otp_phone', $phone);
        if (app()->isLocal() || app()->runningUnitTests() || config('crm.otp.debug')) {
            $response->with('debug_otp', $otp);
        }

        return $response;
    }

    public function verify(Request $request, CrmNotifier $notifier): RedirectResponse
    {
        $data = $request->validate(['otp' => ['required', 'digits:6']]);
        $userId = $request->session()->get('crm_otp_user_id');
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

        $user = CrmUser::query()->whereKey($userId)->where('is_active', true)->first();
        if (! $user) {
            return redirect()->route('crm.login')->withErrors(['phone' => 'Your CRM access is inactive.']);
        }

        $record->update(['used_at' => now()]);
        $user->update(['last_login_at' => now()]);
        $request->session()->regenerate();
        $request->session()->put('crm_user_id', $user->id);
        $request->session()->forget(['crm_otp_user_id', 'crm_otp_phone', 'crm_otp_delivery']);

        $notifier->sendToUsers(
            [$user],
            'Successful One Degree CRM sign-in',
            'New CRM sign-in',
            'Your One Degree CRM account was signed in successfully.',
            [
                'Account' => $user->name,
                'Time' => now()->format('d M Y, g:i A'),
                'IP address' => $request->ip() ?: 'Unavailable',
            ],
            route('crm.dashboard'),
        );

        return redirect()->intended(route('crm.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
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
