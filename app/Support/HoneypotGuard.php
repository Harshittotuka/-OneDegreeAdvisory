<?php

namespace App\Support;

use App\Models\CrmSpamAttempt;
use Illuminate\Http\Request;

/**
 * Silent bot trap shared by every public lead-capture form: a hidden input
 * real visitors never see or fill, but a script that blindly fills every
 * field it finds does. A hit skips lead creation and mail entirely — the
 * caller still returns its normal success response, so the bot has no
 * signal that it was caught — but the attempt is logged (with IP) so
 * repeat offenders show up in the CRM instead of just vanishing.
 */
class HoneypotGuard
{
    public const FIELD = 'website';

    public static function triggered(Request $request): bool
    {
        return trim((string) $request->input(self::FIELD)) !== '';
    }

    public static function log(Request $request, string $source): void
    {
        CrmSpamAttempt::query()->create([
            'source' => $source,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'payload' => $request->except([self::FIELD, '_token']),
        ]);
    }
}
