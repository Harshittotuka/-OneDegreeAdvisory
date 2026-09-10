<?php

namespace App\Support;

use App\Mail\ProfileReportPartnerMail;
use App\Mail\ProfileReportTeamMail;
use App\Mail\ProfileReportThankYouMail;
use App\Models\CrmPartnerCode;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the emails fired when a Student Profiler submission completes:
 *   1. team notification → admissions mailbox (config site.forms.profiler.to)
 *   2. thank-you         → the student (only when a valid email was provided)
 *   3. referral notice   → the partner whose code was on the link, when there
 *                          was one. It carries the student's contact details and
 *                          the key facts, not the full report PDF: the PDF is an
 *                          internal working document, and the partner referred
 *                          the student rather than being retained to advise them.
 *
 * Direct SMTP, no queue — mirrors the Contact/Careers forms. Each send is
 * best-effort and isolated: a failure is reported but never bubbles up, so a
 * mail hiccup can't undo the already-recorded submission or break the wizard's
 * success response.
 */
class ProfileReportNotifier
{
    /**
     * @param  array<string,mixed>  $data     Built by ProfileReportBuilder::build().
     * @param  CrmPartnerCode|null  $partner  Resolved from a ?partner=CODE link.
     */
    public static function notify(array $data, ?CrmPartnerCode $partner = null): void
    {
        $mailer = config('site.forms.profiler.mailer');

        // Referral attribution reaches every template through $data, so the team
        // notification says who sent the student without a second parameter.
        if ($partner) {
            $data['partner'] = [
                'company' => $partner->company_name,
                'code' => $partner->code,
                'contact' => $partner->contact_name,
                'email' => $partner->email,
            ];
        }

        // Render the formatted report PDF once and attach it to both emails.
        // Best-effort: if it fails the mails still go out (without the file).
        $pdf     = null;
        $pdfName = ProfileReportPdf::filename($data);
        try {
            $pdf = ProfileReportPdf::render($data);
        } catch (\Throwable $e) {
            report($e);
        }

        // 1) Team notification — always.
        try {
            Mail::mailer($mailer)
                ->to(config('site.forms.profiler.to'))
                ->send(new ProfileReportTeamMail($data, $pdf, $pdfName));
        } catch (\Throwable $e) {
            report($e);
        }

        // 2) Applicant thank-you — only with a valid email.
        if (filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::mailer($mailer)
                    ->to($data['email'])
                    ->send(new ProfileReportThankYouMail($data, $pdf, $pdfName));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // 3) Referral notice to the partner. Isolated like the rest: a partner
        // whose mailbox is bouncing must not cost us the team notification.
        if ($partner && filter_var($partner->email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::mailer($mailer)
                    ->to($partner->email)
                    ->send(new ProfileReportPartnerMail($data, $partner));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
