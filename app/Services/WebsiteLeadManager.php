<?php

namespace App\Services;

use App\Models\CrmLead;
use App\Models\CrmLeadActivity;
use App\Models\CrmSubscriber;
use App\Models\CrmWebsiteSubmission;
use App\Models\PaymentAttempt;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WebsiteLeadManager
{
    public function capture(
        string $source,
        string $sourceLabel,
        ?string $degree,
        array $sections,
        array $meta = [],
        ?string $externalId = null,
        CarbonInterface|string|null $submittedAt = null,
    ): CrmWebsiteSubmission {
        return DB::transaction(function () use ($source, $sourceLabel, $degree, $sections, $meta, $externalId, $submittedAt): CrmWebsiteSubmission {
            if ($externalId !== null && ($existing = CrmWebsiteSubmission::query()->where('external_id', $externalId)->first())) {
                return $existing;
            }

            $name = trim((string) ($meta['name'] ?? ''));
            $email = strtolower(trim((string) ($meta['email'] ?? '')));
            $phone = $this->normalisePhone((string) ($meta['phone'] ?? ''));
            $lead = $this->findLead($phone, $email);

            if (! $lead) {
                $lead = CrmLead::query()->create([
                    'lead_number' => 'PENDING-'.Str::random(11),
                    'name' => mb_substr($name ?: ($email ? Str::before($email, '@') : $sourceLabel.' lead'), 0, 150),
                    'phone' => $phone ?: null,
                    'email' => $email ?: null,
                    'course_interest' => $this->answer($sections, ['Course / program', 'Career', 'Service needed', 'Study level']),
                    'country_interest' => $this->answer($sections, ['Country of study', 'Destination country', 'Preferred country']),
                    'category' => $this->category($source),
                    'priority' => 'medium',
                    'source' => $sourceLabel,
                    'lead_origin' => 'website',
                    'lead_type' => $this->leadType($source),
                    'status' => 'new',
                    'profile' => ['latest_source' => $source, 'latest_degree' => $degree],
                ]);
                $lead->update(['lead_number' => 'OD-'.str_pad((string) (10000 + $lead->id), 5, '0', STR_PAD_LEFT)]);
            } else {
                $updates = array_filter([
                    'phone' => $lead->phone ?: ($phone ?: null),
                    'email' => $lead->email ?: ($email ?: null),
                    'course_interest' => $lead->course_interest ?: $this->answer($sections, ['Course / program', 'Career', 'Service needed', 'Study level']),
                    'country_interest' => $lead->country_interest ?: $this->answer($sections, ['Country of study', 'Destination country', 'Preferred country']),
                ], fn ($value) => $value !== null && $value !== '');
                if ($source !== 'newsletter') {
                    $updates['lead_type'] = $this->leadType($source);
                    if ($lead->created_by === null && $lead->lead_origin !== 'enrollment') {
                        $updates['lead_origin'] = 'website';
                        $updates['source'] = $sourceLabel;
                    }
                }
                if ($updates !== []) {
                    $lead->update($updates);
                }
            }

            $when = $submittedAt ? Carbon::parse($submittedAt) : now();
            $submission = CrmWebsiteSubmission::query()->create([
                'crm_lead_id' => $lead->id,
                'external_id' => $externalId,
                'source' => $source,
                'source_label' => $sourceLabel,
                'degree' => $degree,
                'sections' => $sections ?: null,
                'meta' => $meta ?: null,
                'submitted_at' => $when,
            ]);

            CrmLeadActivity::query()->create([
                'crm_lead_id' => $lead->id,
                'type' => 'website_submission',
                'body' => 'Received a '.$sourceLabel.' website submission.',
                'metadata' => ['submission_id' => $submission->id, 'source' => $source],
                'created_at' => $when,
                'updated_at' => $when,
            ]);

            return $submission->load('lead');
        });
    }

    public function captureNewsletter(string $email, string $sourceLabel): CrmSubscriber
    {
        $email = strtolower(trim($email));

        return CrmSubscriber::query()->updateOrCreate(
            ['email' => $email],
            [
                'source' => $sourceLabel ?: 'Newsletter',
                'status' => 'active',
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ],
        );
    }

    public function capturePayment(PaymentAttempt $attempt): CrmLead
    {
        return DB::transaction(function () use ($attempt): CrmLead {
            if ($attempt->crm_lead_id && $attempt->lead) {
                return $attempt->lead;
            }

            $phone = $this->normalisePhone((string) $attempt->customer_phone);
            $email = strtolower(trim((string) $attempt->customer_email));
            $lead = $this->findLead($phone, $email);

            if (! $lead) {
                $lead = CrmLead::query()->create([
                    'lead_number' => 'PENDING-'.Str::random(11),
                    'name' => mb_substr(trim($attempt->customer_name), 0, 150),
                    'phone' => $phone ?: null,
                    'email' => $email ?: null,
                    'course_interest' => $attempt->item_name,
                    'category' => $attempt->page_slug === 'test-preparation' ? 'test_prep' : 'other',
                    'priority' => 'medium',
                    'source' => str($attempt->page_slug)->replace('-', ' ')->title().' checkout',
                    'lead_origin' => 'enrollment',
                    'lead_type' => 'enrollment',
                    'status' => 'new',
                ]);
                $lead->update(['lead_number' => 'OD-'.str_pad((string) (10000 + $lead->id), 5, '0', STR_PAD_LEFT)]);
            }

            $attempt->update(['crm_lead_id' => $lead->id]);
            CrmLeadActivity::query()->create([
                'crm_lead_id' => $lead->id,
                'type' => 'enrollment_started',
                'body' => 'Started enrollment checkout for '.$attempt->item_name.'.',
                'metadata' => ['payment_attempt_id' => $attempt->id],
            ]);

            return $lead;
        });
    }

    public function syncPaymentStatus(PaymentAttempt $attempt): void
    {
        if (! $attempt->crm_lead_id) {
            $this->capturePayment($attempt);
            $attempt->refresh();
        }
        if (! $attempt->lead) {
            return;
        }

        CrmLeadActivity::query()->create([
            'crm_lead_id' => $attempt->crm_lead_id,
            'type' => 'payment_'.$attempt->status,
            'body' => 'Payment for '.$attempt->item_name.' is now '.str_replace('_', ' ', $attempt->status).'.',
            'metadata' => ['payment_attempt_id' => $attempt->id, 'amount' => $attempt->amount, 'currency' => $attempt->currency],
        ]);

        if ($attempt->status === 'paid') {
            $attempt->lead->update([
                'is_student' => true,
                'status' => 'converted',
                'student_category' => 'paid',
                'student_stage' => $attempt->lead->student_stage ?: 'doc_pending',
                'enrollment_amount' => (int) round($attempt->amount / 100),
                'enrollment_date' => ($attempt->paid_at ?: now())->toDateString(),
                'payment_reference' => $attempt->razorpay_payment_id ?: $attempt->razorpay_order_id,
            ]);
        }
    }

    private function findLead(string $phone, string $email): ?CrmLead
    {
        if ($phone === '' && $email === '') {
            return null;
        }

        if ($phone !== '' && $email !== '') {
            $exact = CrmLead::withTrashed()
                ->where('phone', $phone)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
            if ($exact) {
                return $this->restoreLead($exact);
            }

            $phoneMatches = CrmLead::withTrashed()->where('phone', $phone)->get();
            $emailMatches = CrmLead::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->get();

            if ($phoneMatches->isEmpty() && $emailMatches->isEmpty()) {
                return null;
            }

            if ($phoneMatches->count() === 1 && $emailMatches->isEmpty() && blank($phoneMatches->first()->email)) {
                return $this->restoreLead($phoneMatches->first());
            }

            if ($emailMatches->count() === 1 && $phoneMatches->isEmpty() && blank($emailMatches->first()->phone)) {
                return $this->restoreLead($emailMatches->first());
            }

            $this->identityConflict();
        }

        $matches = $phone !== ''
            ? CrmLead::withTrashed()->where('phone', $phone)->get()
            : CrmLead::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->get();

        if ($matches->count() > 1) {
            $this->identityConflict();
        }

        return $matches->isEmpty() ? null : $this->restoreLead($matches->first());
    }

    private function restoreLead(CrmLead $lead): CrmLead
    {
        if ($lead?->trashed()) {
            $lead->restore();
        }

        return $lead;
    }

    private function identityConflict(): never
    {
        throw ValidationException::withMessages([
            'contact' => 'The phone number and email do not match the same existing contact. Please verify the details and try again.',
        ]);
    }

    private function normalisePhone(string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', $phone);

        return $digits === '' ? '' : substr($digits, -10);
    }

    private function answer(array $sections, array $labels): ?string
    {
        foreach ($sections as $section) {
            foreach ((array) ($section['answers'] ?? []) as $answer) {
                if (in_array((string) ($answer['label'] ?? ''), $labels, true)) {
                    return mb_substr(implode(', ', (array) ($answer['value'] ?? [])), 0, 180) ?: null;
                }
            }
        }

        return null;
    }

    private function category(string $source): string
    {
        return match ($source) {
            'visa-mock' => 'visa',
            'test-prep-enrollment' => 'test_prep',
            'profiler' => 'other',
            default => 'other',
        };
    }

    private function leadType(string $source): string
    {
        return match ($source) {
            'profiler' => 'student_profiler',
            'loan-acco' => 'loan_accommodation',
            'sop' => 'statement_of_purpose',
            'visa-mock' => 'visa_mock_interview',
            'career-library' => 'career_library',
            default => 'general',
        };
    }
}
