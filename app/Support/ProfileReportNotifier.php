<?php

namespace App\Support;

use App\Mail\ProfileReportTeamMail;
use App\Mail\ProfileReportThankYouMail;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the two emails fired when a profiler/evaluator submission completes:
 *   1. team notification → admissions mailbox (config site.forms.profiler.to)
 *   2. thank-you         → the student (only when a valid email was provided)
 *
 * Direct SMTP, no queue — mirrors the Contact/Careers forms. Each send is
 * best-effort and isolated: a failure is reported but never bubbles up, so a
 * mail hiccup can't undo the already-recorded submission or break the wizard's
 * success response.
 */
class ProfileReportNotifier
{
    /** @param array<string,mixed> $data Built by ProfileReportBuilder::build(). */
    public static function notify(array $data): void
    {
        $mailer = config('site.forms.profiler.mailer');

        // 1) Team notification — always.
        try {
            Mail::mailer($mailer)
                ->to(config('site.forms.profiler.to'))
                ->send(new ProfileReportTeamMail($data));
        } catch (\Throwable $e) {
            report($e);
        }

        // 2) Applicant thank-you — only with a valid email.
        if (filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::mailer($mailer)
                    ->to($data['email'])
                    ->send(new ProfileReportThankYouMail($data));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
