<?php

namespace App\Services;

use App\Models\CrmGoogleAccount;
use App\Models\CrmUser;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Real Google Meet rooms, made in each counsellor's own Google Calendar.
 *
 * Talks to Google over plain HTTPS — the OAuth endpoints and Calendar v3 —
 * with no SDK. A room is a calendar event on the counsellor's primary
 * calendar carrying a Meet conference and no guests, written with
 * sendUpdates=none: Google emails nobody; the planner's own email carries
 * the link.
 *
 * Staying connected: the refresh token is asked for with offline access and
 * forced consent, so Google always issues one; it is kept encrypted and is
 * never replaced by an empty value. Access tokens last an hour and are renewed
 * here, a few minutes early, whenever they are needed — the counsellor never
 * sees one expire. keepAlive() renews every connection on a schedule so that
 * none is left unused long enough for Google to drop it. If Google does
 * refuse a refresh token (revoked, or the app is still in Testing), the
 * connection is marked for reconnecting rather than failing quietly.
 */
class GoogleCalendar
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';

    private const EVENTS_URL = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';

    private const CALENDAR_SCOPE = 'https://www.googleapis.com/auth/calendar.events';

    public function configured(): bool
    {
        return trim((string) config('google.client_id')) !== '' && trim((string) config('google.client_secret')) !== '';
    }

    /**
     * Where Google sends the counsellor back. Google refuses plain http for
     * anything but localhost, and a server's APP_URL may still say http, so
     * the scheme is fixed here rather than trusted.
     */
    public function redirectUri(): string
    {
        $configured = trim((string) config('google.redirect'));
        if ($configured !== '') {
            return $configured;
        }
        $url = route('crm.google.callback');
        $host = (string) parse_url($url, PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1'], true) ? $url : (string) preg_replace('#^http://#', 'https://', $url);
    }

    public function authUrl(string $state, ?string $loginHint = null): string
    {
        return self::AUTH_URL.'?'.http_build_query(array_filter([
            'client_id' => config('google.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', config('google.scopes')),
            // offline + consent: Google issues a refresh token every time,
            // so a reconnect always leaves a working one behind.
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
            'login_hint' => $loginHint,
        ]), '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Swap the code Google sent back for tokens and store them against the
     * counsellor.
     *
     * @throws GoogleCalendarException
     */
    public function connect(CrmUser $user, string $code): CrmGoogleAccount
    {
        $response = $this->http()->asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('google.client_id'),
            'client_secret' => config('google.client_secret'),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);
        if (! $response->successful()) {
            $this->logFailure('exchange', $user->id, $response);

            throw new GoogleCalendarException('Google did not accept the sign-in. Try connecting again.');
        }

        $tokens = $response->json();
        $scopes = explode(' ', (string) ($tokens['scope'] ?? ''));
        if (! in_array(self::CALENDAR_SCOPE, $scopes, true)) {
            // Google's consent screen lets the calendar permission be unticked.
            throw new GoogleCalendarException('Google was connected without calendar access, so it cannot make Meet rooms. Connect again and leave the calendar permission ticked.');
        }

        $account = CrmGoogleAccount::query()->firstOrNew(['crm_user_id' => $user->id]);
        $refresh = (string) ($tokens['refresh_token'] ?? '');
        if ($refresh === '' && ! $account->exists) {
            throw new GoogleCalendarException('Google did not hand over a lasting connection. Remove One Degree from your Google account\'s third-party access, then connect again.');
        }

        $account->fill([
            'google_email' => $this->emailFromIdToken((string) ($tokens['id_token'] ?? '')) ?? $account->google_email,
            'access_token' => (string) ($tokens['access_token'] ?? ''),
            'access_token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3600)),
            'scopes' => implode(' ', $scopes),
            'connected_at' => now(),
            'last_refreshed_at' => now(),
            'needs_reconnect_at' => null,
            'last_error' => null,
        ]);
        // Never trade a working refresh token for nothing.
        if ($refresh !== '') {
            $account->refresh_token = $refresh;
        }
        $account->save();

        return $account;
    }

    /** Disconnect: tell Google to forget the grant, then forget it here. */
    public function disconnect(CrmGoogleAccount $account): void
    {
        try {
            $this->http()->asForm()->post(self::REVOKE_URL, ['token' => $account->refresh_token]);
        } catch (\Throwable $e) {
            Log::info('Google revoke failed; removing the connection anyway', ['user' => $account->crm_user_id, 'error' => $e->getMessage()]);
        }
        $account->delete();
    }

    /**
     * A current access token, renewed first if it is about to run out.
     *
     * @throws GoogleCalendarException
     */
    public function accessToken(CrmGoogleAccount $account): string
    {
        if (! $account->works()) {
            throw new GoogleCalendarException($this->reconnectMessage($account));
        }
        $early = (int) config('google.refresh_early_seconds', 300);
        if ($account->access_token && $account->access_token_expires_at?->isAfter(now()->addSeconds($early))) {
            return (string) $account->access_token;
        }

        // Two requests at once must not both renew.
        return Cache::lock('google-refresh:'.$account->id, 20)->block(10, function () use ($account, $early): string {
            $account->refresh();
            if ($account->access_token && $account->access_token_expires_at?->isAfter(now()->addSeconds($early))) {
                return (string) $account->access_token;
            }
            if (! $this->renew($account)) {
                throw new GoogleCalendarException($this->reconnectMessage($account));
            }

            return (string) $account->access_token;
        });
    }

    /**
     * Use the refresh token now. Returns false (and marks the connection) if
     * Google has stopped accepting it.
     */
    public function renew(CrmGoogleAccount $account): bool
    {
        try {
            $response = $this->http()->asForm()->post(self::TOKEN_URL, [
                'client_id' => config('google.client_id'),
                'client_secret' => config('google.client_secret'),
                'refresh_token' => $account->refresh_token,
                'grant_type' => 'refresh_token',
            ]);
        } catch (\Throwable $e) {
            // Google unreachable is not the counsellor's problem to fix: try
            // again next time, keep the connection as it is.
            throw new GoogleCalendarException('Couldn\'t reach Google just now. Try again in a moment.', previous: $e);
        }

        if ($response->successful()) {
            $tokens = $response->json();
            $account->forceFill([
                'access_token' => (string) ($tokens['access_token'] ?? ''),
                'access_token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3600)),
                'last_refreshed_at' => now(),
                'last_error' => null,
            ]);
            // Google normally keeps the same refresh token; if it ever sends
            // a new one, that is the one to keep.
            if (! empty($tokens['refresh_token'])) {
                $account->refresh_token = (string) $tokens['refresh_token'];
            }
            $account->save();

            return true;
        }

        $error = (string) $response->json('error');
        if ($error === 'invalid_grant' || $error === 'unauthorized_client') {
            $account->forceFill([
                'needs_reconnect_at' => now(),
                'access_token' => null,
                'last_error' => $this->explainRefusal($account),
            ])->save();
            Log::warning('Google refresh token refused', ['user' => $account->crm_user_id, 'error' => $error, 'connected_at' => $account->connected_at?->toIso8601String()]);

            return false;
        }

        $this->logFailure('refresh', $account->crm_user_id, $response);

        throw new GoogleCalendarException('Google is having trouble right now. Try again in a moment.');
    }

    /**
     * Renew every working connection that has not been used lately, and clear
     * away rooms made for dialogs that were never saved.
     *
     * @return array{renewed: int, lost: int, failed: int, rooms: int}
     */
    public function keepAlive(?int $olderThanHours = 20): array
    {
        $out = ['renewed' => 0, 'lost' => 0, 'failed' => 0, 'rooms' => 0];
        if (! $this->configured()) {
            return $out;
        }

        CrmGoogleAccount::query()->whereNull('needs_reconnect_at')->each(function (CrmGoogleAccount $account) use (&$out, $olderThanHours): void {
            try {
                if ($olderThanHours === null || ! $account->last_refreshed_at || $account->last_refreshed_at->isBefore(now()->subHours($olderThanHours))) {
                    $this->renew($account) ? $out['renewed']++ : $out['lost']++;
                }
                if ($account->works()) {
                    $out['rooms'] += $this->clearUnclaimedRooms($account);
                }
            } catch (\Throwable $e) {
                $out['failed']++;
                Log::warning('Google keep-alive failed', ['user' => $account->crm_user_id, 'error' => $e->getMessage()]);
            }
        });

        return $out;
    }

    /**
     * After the response is sent, renew this counsellor's connection if it has
     * gone a few days without one. Lets a server with no scheduler keep its
     * tokens in use.
     */
    public function keepAliveSoon(CrmUser $user): void
    {
        if (! $this->configured()) {
            return;
        }
        $days = (int) config('google.keep_alive_days', 3);
        app()->terminating(function () use ($user, $days): void {
            try {
                $account = CrmGoogleAccount::query()->where('crm_user_id', $user->id)->whereNull('needs_reconnect_at')->first();
                if ($account && (! $account->last_refreshed_at || $account->last_refreshed_at->isBefore(now()->subDays($days)))) {
                    $this->renew($account);
                }
            } catch (\Throwable $e) {
                Log::info('Google keep-alive on request failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            }
        });
    }

    /**
     * Make a Meet room: an event on the counsellor's calendar with a fresh
     * conference and nobody invited.
     *
     * @param  array{title?: string, date?: string, time?: string, minutes?: int, notes?: string}  $meeting
     * @return array{event: string, link: string}
     *
     * @throws GoogleCalendarException
     */
    public function createRoom(CrmGoogleAccount $account, array $meeting, string $studentName): array
    {
        $response = $this->calendar($account)
            ->withQueryParameters(['conferenceDataVersion' => 1, 'sendUpdates' => 'none'])
            ->post(self::EVENTS_URL, $this->eventBody($meeting, $studentName) + [
                'conferenceData' => ['createRequest' => [
                    'requestId' => (string) Str::uuid(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ]],
            ]);
        $event = $this->eventOrFail($account, $response, 'create');

        $link = $this->meetLink($event);
        if ($link === null && ! empty($event['id'])) {
            // Google can take a moment to attach the conference.
            usleep(800_000);
            $again = $this->calendar($account)->get(self::EVENTS_URL.'/'.rawurlencode((string) $event['id']));
            $link = $again->successful() ? $this->meetLink($again->json()) : null;
        }
        if ($link === null) {
            $this->deleteRoom($account, (string) ($event['id'] ?? ''));

            throw new GoogleCalendarException('Google made the event but no Meet room for it. Check Google Meet is switched on for your account.');
        }

        return ['event' => (string) $event['id'], 'link' => $link];
    }

    /**
     * Keep the room's calendar entry in step with the meeting.
     *
     * @throws GoogleCalendarException
     */
    public function updateRoom(CrmGoogleAccount $account, string $eventId, array $meeting, string $studentName): void
    {
        $response = $this->calendar($account)
            ->withQueryParameters(['sendUpdates' => 'none'])
            ->patch(self::EVENTS_URL.'/'.rawurlencode($eventId), $this->eventBody($meeting, $studentName));
        if ($response->status() === 404 || $response->status() === 410) {
            return; // the counsellor deleted it in Google; nothing to keep in step
        }
        $this->eventOrFail($account, $response, 'update');
    }

    /** Remove a room. Already gone counts as done. */
    public function deleteRoom(CrmGoogleAccount $account, string $eventId): bool
    {
        if ($eventId === '') {
            return true;
        }
        try {
            $response = $this->calendar($account)
                ->withQueryParameters(['sendUpdates' => 'none'])
                ->delete(self::EVENTS_URL.'/'.rawurlencode($eventId));
        } catch (\Throwable $e) {
            Log::info('Google room delete failed', ['user' => $account->crm_user_id, 'event' => $eventId, 'error' => $e->getMessage()]);

            return false;
        }

        return $response->successful() || in_array($response->status(), [404, 410], true);
    }

    /**
     * A room made for a meeting dialog, not yet on any meeting. Kept on the
     * account so a dialog closed without saving does not leave it behind.
     */
    public function rememberRoom(CrmGoogleAccount $account, array $room): void
    {
        $rooms = array_values(array_filter($account->pending_rooms ?? [], 'is_array'));
        $rooms[] = ['event' => $room['event'], 'link' => $room['link'], 'at' => now()->toIso8601String()];
        $account->forceFill(['pending_rooms' => array_slice($rooms, -40)])->save();
    }

    /**
     * Take a pending room off the list. Returns it if it was there.
     *
     * @return array{event: string, link: string}|null
     */
    public function claimRoom(CrmGoogleAccount $account, string $eventId): ?array
    {
        $rooms = array_values(array_filter($account->pending_rooms ?? [], 'is_array'));
        $found = null;
        $left = [];
        foreach ($rooms as $room) {
            if ($found === null && ($room['event'] ?? null) === $eventId) {
                $found = ['event' => (string) $room['event'], 'link' => (string) ($room['link'] ?? '')];
            } else {
                $left[] = $room;
            }
        }
        if ($found !== null) {
            $account->forceFill(['pending_rooms' => $left])->save();
        }

        return $found;
    }

    public function clearUnclaimedRooms(CrmGoogleAccount $account): int
    {
        $cutoff = now()->subHours((int) config('google.unclaimed_room_hours', 12));
        $cleared = 0;
        $keep = [];
        foreach (array_filter($account->pending_rooms ?? [], 'is_array') as $room) {
            $at = CarbonImmutable::make($room['at'] ?? null);
            if ($at && $at->isAfter($cutoff)) {
                $keep[] = $room;
            } elseif ($this->deleteRoom($account, (string) ($room['event'] ?? ''))) {
                $cleared++;
            } else {
                $keep[] = $room;
            }
        }
        if ($cleared > 0) {
            $account->forceFill(['pending_rooms' => $keep])->save();
        }

        return $cleared;
    }

    /* ------------------------------------------------------------ helpers */

    private function eventBody(array $meeting, string $studentName): array
    {
        $tz = (string) config('google.timezone', 'Asia/Kolkata');
        $date = (string) ($meeting['date'] ?? '') ?: now($tz)->toDateString();
        $time = (string) ($meeting['time'] ?? '') ?: '09:00';
        $start = CarbonImmutable::createFromFormat('Y-m-d H:i', $date.' '.$time, $tz) ?: now($tz)->toImmutable();
        $end = $start->addMinutes(max(5, min(480, (int) ($meeting['minutes'] ?? 45))));
        $title = trim((string) ($meeting['title'] ?? '')) ?: 'Meeting';

        return [
            'summary' => $title.' — '.$studentName,
            'description' => trim(implode("\n\n", array_filter([
                (string) ($meeting['notes'] ?? ''),
                'Booked from the One Degree Advisory CRM for '.$studentName.'.',
            ]))),
            'start' => ['dateTime' => $start->format('Y-m-d\TH:i:s'), 'timeZone' => $tz],
            'end' => ['dateTime' => $end->format('Y-m-d\TH:i:s'), 'timeZone' => $tz],
            'guestsCanInviteOthers' => false,
        ];
    }

    private function meetLink(array $event): ?string
    {
        if (! empty($event['hangoutLink']) && str_starts_with((string) $event['hangoutLink'], 'https://')) {
            return (string) $event['hangoutLink'];
        }
        foreach ($event['conferenceData']['entryPoints'] ?? [] as $entry) {
            if (($entry['entryPointType'] ?? null) === 'video' && str_starts_with((string) ($entry['uri'] ?? ''), 'https://')) {
                return (string) $entry['uri'];
            }
        }

        return null;
    }

    /** @throws GoogleCalendarException */
    private function eventOrFail(CrmGoogleAccount $account, Response $response, string $what): array
    {
        if ($response->successful()) {
            return (array) $response->json();
        }
        if ($response->status() === 401) {
            // The access token was refused before its time: drop it, so the
            // next request renews with the refresh token.
            $account->forceFill(['access_token' => null, 'access_token_expires_at' => null])->save();

            throw new GoogleCalendarException('Google asked for the sign-in again. Try once more.');
        }
        $this->logFailure($what, $account->crm_user_id, $response);
        if ($response->status() === 403) {
            throw new GoogleCalendarException('Google refused to change your calendar. Check the Google Calendar API is enabled for the CRM\'s Google project.');
        }

        throw new GoogleCalendarException('Google could not save the meeting room just now. Try again in a moment.');
    }

    private function calendar(CrmGoogleAccount $account): PendingRequest
    {
        return $this->http()->withToken($this->accessToken($account))->acceptJson()->asJson();
    }

    private function http(): PendingRequest
    {
        return Http::timeout((int) config('google.timeout', 15))->connectTimeout(8);
    }

    /**
     * The address on the account just connected, read from the ID token. It
     * comes straight from Google's token endpoint over TLS, so its signature
     * does not need checking (Google's own guidance for this case).
     */
    private function emailFromIdToken(string $jwt): ?string
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            return null;
        }
        $claims = json_decode((string) base64_decode(strtr($parts[1], '-_', '+/'), true), true);
        $email = is_array($claims) ? (string) ($claims['email'] ?? '') : '';

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : null;
    }

    private function explainRefusal(CrmGoogleAccount $account): string
    {
        // Refused about a week after connecting is the Testing-mode expiry.
        $days = $account->connected_at ? $account->connected_at->diffInDays(now()) : null;
        if ($days !== null && $days >= 6 && $days <= 8) {
            return 'Google ended the connection after 7 days, which it does while the CRM\'s Google app is in Testing. Publish the app in Google Cloud, then connect again.';
        }

        return 'Google stopped accepting this connection (access was removed, or the password or permissions changed). Connect again.';
    }

    private function reconnectMessage(CrmGoogleAccount $account): string
    {
        return $account->last_error ?: 'Your Google connection has stopped working. Connect Google again.';
    }

    private function logFailure(string $what, int $userId, Response $response): void
    {
        $error = $response->json('error');
        Log::warning('Google Calendar '.$what.' failed', [
            'user' => $userId,
            'status' => $response->status(),
            'error' => Str::limit(is_array($error) ? (string) ($error['message'] ?? json_encode($error)) : (string) ($error ?? $response->body()), 300),
        ]);
    }
}
