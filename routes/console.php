<?php

use App\Mail\CareerApplicationMail;
use App\Mail\CareerThankYouMail;
use App\Mail\ContactEnquiryMail;
use App\Mail\ContactThankYouMail;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;

Schedule::command('crm:send-follow-up-reminders')->dailyAt('08:00')->timezone('Asia/Kolkata')->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:doctor {--mailer=*}', function () {
    $errors = [];
    $warnings = [];
    $mailers = config('mail.mailers', []);
    $isPlaceholder = function (mixed $value): bool {
        $value = strtolower(trim((string) $value));

        return str_contains($value, 'paste_')
            || str_contains($value, '_here')
            || str_contains($value, 'your-google-app-password')
            || str_contains($value, 'your-smtp-password')
            || str_contains($value, 'your-mailbox-password');
    };

    $mailerNames = $this->option('mailer') ?: [
        config('mail.default'),
        config('site.forms.contact.mailer'),
        config('site.forms.careers.mailer'),
        config('crm.email.mailer'),
    ];

    $mailerNames = array_values(array_unique(array_filter(array_map(
        fn ($mailer) => is_string($mailer) ? trim($mailer) : null,
        $mailerNames,
    ))));

    foreach ([
        'MAIL_FROM_ADDRESS' => config('mail.from.address'),
        'CONTACT_FORM_TO' => config('site.forms.contact.to'),
        'CONTACT_FORM_FROM' => config('site.forms.contact.from'),
        'CAREERS_FORM_TO' => config('site.forms.careers.to'),
        'CAREERS_FORM_FROM' => config('site.forms.careers.from'),
    ] as $label => $address) {
        if (Validator::make(['email' => $address], ['email' => ['required', 'email']])->fails()) {
            $errors[] = "{$label} must be a valid email address.";
        }
    }

    foreach ($mailerNames as $mailer) {
        if (! array_key_exists($mailer, $mailers)) {
            $errors[] = "Mailer [{$mailer}] is not configured in config/mail.php.";

            continue;
        }

        $transport = (string) ($mailers[$mailer]['transport'] ?? $mailer);

        if (app()->environment('production') && in_array($transport, ['log', 'array'], true)) {
            $errors[] = "Production mailer [{$mailer}] uses {$transport}; use smtp for real delivery.";
        }

        if ($transport !== 'smtp') {
            continue;
        }

        foreach (['host', 'port', 'username', 'password'] as $key) {
            $value = $mailers[$mailer][$key] ?? null;

            if ($value === null || trim((string) $value) === '' || strtolower(trim((string) $value)) === 'null') {
                $errors[] = "SMTP mailer [{$mailer}] is missing [{$key}].";
            } elseif ($isPlaceholder($value)) {
                $errors[] = "SMTP mailer [{$mailer}] has a placeholder value for [{$key}]. Replace it in .env.";
            }
        }

        $scheme = strtolower((string) ($mailers[$mailer]['scheme'] ?? ''));
        if ($scheme !== '' && $scheme !== 'null' && ! in_array($scheme, ['smtp', 'smtps'], true)) {
            $errors[] = "SMTP mailer [{$mailer}] uses unsupported scheme [{$scheme}]. Use smtp for port 587 or smtps for port 465.";
        }
    }

    foreach ($warnings as $warning) {
        $this->warn($warning);
    }

    foreach ($errors as $error) {
        $this->error($error);
    }

    if ($errors !== []) {
        return 1;
    }

    $this->info('Mail configuration looks ready for direct SMTP delivery.');
    $this->line('Checked mailers: '.implode(', ', $mailerNames));

    return 0;
})->purpose('Validate the direct SMTP mail configuration');

Artisan::command('mail:test {email} {--mailer=}', function (string $email) {
    if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
        $this->error('Please provide a valid recipient email address.');

        return 1;
    }

    $mailer = $this->option('mailer') ?: config('mail.default');

    if ($this->call('mail:doctor', ['--mailer' => [$mailer]]) !== 0) {
        return 1;
    }

    Mail::mailer($mailer)->raw(
        "One Degree Advisory SMTP test\n\n"
        ."Mailer: {$mailer}\n"
        .'Environment: '.app()->environment()."\n"
        .'Sent at: '.now()->toDateTimeString(),
        function ($message) use ($email) {
            $message->to($email)->subject('One Degree Advisory SMTP test');
        }
    );

    $this->info("SMTP test email sent to {$email}.");

    return 0;
})->purpose('Send a direct SMTP test email');

Artisan::command('mail:test-flow {type} {email} {--team-to=}', function (string $type, string $email) {
    $type = strtolower(trim($type));

    if (! in_array($type, ['contact', 'careers'], true)) {
        $this->error('Type must be contact or careers.');

        return 1;
    }

    if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
        $this->error('Please provide a valid recipient email address.');

        return 1;
    }

    if ($type === 'contact') {
        $mailer = config('site.forms.contact.mailer') ?: config('mail.default');

        if ($this->call('mail:doctor', ['--mailer' => [$mailer]]) !== 0) {
            return 1;
        }

        $teamTo = $this->option('team-to') ?: config('site.forms.contact.to');
        $data = [
            'name' => 'Harshit Test Student',
            'email' => $email,
            'phone' => '+91 90000 00000',
            'city' => 'Jaipur',
            'destination' => 'United Kingdom',
            'level' => 'Undergraduate',
            'message' => 'This is a test enquiry generated from php artisan mail:test-flow.',
            'consent' => true,
        ];

        Mail::mailer($mailer)->to($teamTo)->send(new ContactEnquiryMail($data));
        Mail::mailer($mailer)->to($email)->send(new ContactThankYouMail($data));

        $this->info("Contact flow sent via {$mailer}.");
        $this->line("Team email: {$teamTo}");
        $this->line("Thank-you email: {$email}");

        return 0;
    }

    $mailer = config('site.forms.careers.mailer') ?: config('mail.default');

    if ($this->call('mail:doctor', ['--mailer' => [$mailer]]) !== 0) {
        return 1;
    }

    $teamTo = $this->option('team-to') ?: config('site.forms.careers.to');
    $data = [
        'name' => 'Harshit Test Applicant',
        'email' => $email,
        'phone' => '+91 90000 00000',
        'linkedin' => 'https://www.linkedin.com/in/test-applicant',
        'role' => 'Admissions Counsellor',
        'experience' => '3 years',
        'message' => 'This is a test application generated from php artisan mail:test-flow.',
        'resume_link' => 'https://example.com/test-resume.pdf',
        'resume' => null,
        'consent' => true,
    ];

    Mail::mailer($mailer)->to($teamTo)->send(new CareerApplicationMail($data));
    Mail::mailer($mailer)->to($email)->send(new CareerThankYouMail($data));

    $this->info("Careers flow sent via {$mailer}.");
    $this->line("Team email: {$teamTo}");
    $this->line("Thank-you email: {$email}");

    return 0;
})->purpose('Send real designed team and thank-you emails for a form flow');
