<?php

namespace App\Services;

use App\Mail\CrmNotificationMail;
use App\Models\CrmLead;
use App\Models\CrmUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CrmNotifier
{
    /**
     * @param  iterable<int, CrmUser>  $users
     * @param  array<string, mixed>  $details
     */
    public function sendToUsers(
        iterable $users,
        string $subject,
        string $headline,
        string $message,
        array $details = [],
        ?string $actionUrl = null,
        string $actionLabel = 'Open CRM',
        bool $includeInactive = false,
    ): int {
        if (! config('crm.email.enabled', true)) {
            return 0;
        }

        $recipients = collect($users)
            ->filter(fn ($user): bool => $user instanceof CrmUser && ($includeInactive || $user->is_active) && filter_var($user->email, FILTER_VALIDATE_EMAIL))
            ->unique(fn (CrmUser $user): string => strtolower((string) $user->email));

        $sent = 0;
        foreach ($recipients as $recipient) {
            try {
                Mail::mailer((string) config('crm.email.mailer', 'contact_form'))
                    ->to($recipient->email, $recipient->name)
                    ->send(new CrmNotificationMail($subject, $headline, $message, $details, $actionUrl, $actionLabel));
                $sent++;
            } catch (Throwable $exception) {
                Log::warning('CRM email notification failed.', [
                    'recipient' => $recipient->email,
                    'subject' => $subject,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    /** @param iterable<int, CrmUser> $extra */
    public function leadRecipients(CrmLead $lead, ?CrmUser $actor = null, iterable $extra = []): Collection
    {
        $recipients = CrmUser::query()
            ->where('role', 'super_admin')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get();

        if ($lead->assignee?->is_active) {
            $recipients->push($lead->assignee);
        }

        $recipients = $recipients->merge(collect($extra));
        if ($actor) {
            $recipients = $recipients->reject(fn (CrmUser $user): bool => $user->id === $actor->id);
        }

        return $recipients->unique('id')->values();
    }

    /** @return array<string, mixed> */
    public function leadDetails(CrmLead $lead): array
    {
        return [
            'Lead ID' => $lead->lead_number,
            'Student' => $lead->name,
            'Phone' => $lead->phone,
            'Email' => $lead->email ?: 'Not provided',
            'Status' => ucfirst(str_replace('_', ' ', $lead->status)),
            'Priority' => ucfirst($lead->priority),
            'Counsellor' => $lead->assignee?->name ?? 'Unassigned',
            'Follow-up' => $lead->follow_up_at?->format('d M Y, g:i A') ?? 'Not scheduled',
        ];
    }
}
