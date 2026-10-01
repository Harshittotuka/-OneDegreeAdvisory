<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmGoogleAccount;
use App\Models\CrmUser;
use App\Services\CrmAuditLogger;
use App\Services\GoogleCalendar;
use App\Services\GoogleCalendarException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * A counsellor connecting their own Google account, so the planner can make
 * real Google Meet rooms in it. Partners never connect one: they cannot book
 * meetings.
 */
class CrmGoogleController extends Controller
{
    private const STATE_KEY = 'google_oauth';

    public function connect(Request $request, GoogleCalendar $google): RedirectResponse
    {
        $user = $this->user($request);
        abort_if($user->isPartner(), 403);
        $back = $this->safeBack($request->query('back'));
        if (! $google->configured()) {
            return $this->backWith($back, 'Google is not set up on this CRM yet.');
        }

        $state = Str::random(48);
        $request->session()->put(self::STATE_KEY, ['state' => $state, 'back' => $back, 'user' => $user->id, 'at' => now()->timestamp]);

        return redirect()->away($google->authUrl($state, $user->googleAccount?->google_email ?: $user->email));
    }

    public function callback(Request $request, GoogleCalendar $google, CrmAuditLogger $audit): RedirectResponse
    {
        $user = $this->user($request);
        abort_if($user->isPartner(), 403);
        $pending = $request->session()->pull(self::STATE_KEY);
        $back = is_array($pending) ? $this->safeBack($pending['back'] ?? null) : route('crm.dashboard');

        $state = (string) $request->query('state', '');
        if (! is_array($pending) || $state === '' || ! hash_equals((string) ($pending['state'] ?? ''), $state)
            || (int) ($pending['user'] ?? 0) !== $user->id || now()->timestamp - (int) ($pending['at'] ?? 0) > 900) {
            return $this->backWith($back, 'That Google sign-in had expired. Connect Google again.');
        }
        if ($request->filled('error')) {
            return $this->backWith($back, $request->query('error') === 'access_denied'
                ? 'Google was not connected — the permission was declined.'
                : 'Google did not complete the sign-in. Try again.');
        }

        try {
            $account = $google->connect($user, (string) $request->query('code', ''));
        } catch (GoogleCalendarException $e) {
            return $this->backWith($back, $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return $this->backWith($back, 'Couldn\'t reach Google just now. Try connecting again.');
        }

        $audit->record($request, $user, 'google_connected', "Connected Google account {$account->google_email}", [
            'subject_type' => CrmGoogleAccount::class, 'subject_id' => $account->id, 'subject_label' => (string) $account->google_email,
        ]);

        return $this->backWith($back, 'Google connected'.($account->google_email ? ' as '.$account->google_email : '').'. Google Meet meetings now get a real room.', true);
    }

    public function disconnect(Request $request, GoogleCalendar $google, CrmAuditLogger $audit): JsonResponse
    {
        $user = $this->user($request);
        $account = $user->googleAccount;
        if ($account) {
            $email = (string) $account->google_email;
            $google->disconnect($account);
            $audit->record($request, $user, 'google_disconnected', "Disconnected Google account {$email}", [
                'subject_type' => CrmGoogleAccount::class, 'subject_id' => $account->id, 'subject_label' => $email,
            ]);
        }

        return response()->json(['ok' => true, 'google' => self::status($user->fresh(), $google)]);
    }

    /** What the planner needs to know about the counsellor's connection. */
    public static function status(CrmUser $user, GoogleCalendar $google): array
    {
        $account = $user->googleAccount;

        return ($account ? $account->toStatusArray() : []) + [
            'configured' => $google->configured(),
            'connected' => false,
            'email' => '',
            'works' => false,
            'problem' => null,
            'connect' => route('crm.google.connect'),
            'disconnect' => route('crm.google.disconnect'),
        ];
    }

    private function backWith(string $back, string $text, bool $ok = false): RedirectResponse
    {
        return redirect()->to($back)->with('google_status', ['ok' => $ok, 'text' => $text]);
    }

    /** Only ever send someone back to a page on this site. */
    private function safeBack(mixed $back): string
    {
        $back = is_string($back) ? trim($back) : '';

        return preg_match('#^/(?![/\\\\])[^\s]*$#', $back) ? $back : route('crm.dashboard');
    }

    private function user(Request $request): CrmUser
    {
        return $request->attributes->get('crm_user');
    }
}
