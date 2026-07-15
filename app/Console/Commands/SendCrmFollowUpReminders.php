<?php

namespace App\Console\Commands;

use App\Models\CrmLead;
use App\Models\CrmLeadActivity;
use App\Services\CrmNotifier;
use Illuminate\Console\Command;

class SendCrmFollowUpReminders extends Command
{
    protected $signature = 'crm:send-follow-up-reminders';

    protected $description = 'Email advance and due-day reminders for CRM follow-ups';

    public function handle(CrmNotifier $notifier): int
    {
        $today = now()->startOfDay();
        $windows = [
            'advance' => [$today->copy()->addDay(), $today->copy()->addDay()->endOfDay()],
            'due' => [$today->copy(), $today->copy()->endOfDay()],
        ];
        $sent = 0;

        foreach ($windows as $type => [$start, $end]) {
            $leads = CrmLead::query()
                ->with('assignee')
                ->whereNull('follow_up_completed_at')
                ->whereBetween('follow_up_at', [$start, $end])
                ->get();

            foreach ($leads as $lead) {
                $date = $lead->follow_up_at->format('Y-m-d');
                $body = ucfirst($type).' follow-up reminder emailed for '.$date.'.';
                if ($lead->activities()->where('type', 'reminder_email')->where('body', $body)->exists()) {
                    continue;
                }

                $label = $type === 'advance' ? 'Follow-up due tomorrow' : 'Follow-up due today';
                $delivered = $notifier->sendToUsers(
                    $notifier->leadRecipients($lead),
                    $label.': '.$lead->name,
                    $label,
                    'A CRM follow-up for '.$lead->name.' is scheduled for '.$lead->follow_up_at->format('d M Y, g:i A').'.',
                    $notifier->leadDetails($lead),
                    route('crm.dashboard', ['view' => 'followups', 'lead' => $lead->id]),
                    'Open follow-up',
                );

                if ($delivered > 0) {
                    CrmLeadActivity::query()->create([
                        'crm_lead_id' => $lead->id,
                        'crm_user_id' => null,
                        'type' => 'reminder_email',
                        'body' => $body,
                        'metadata' => ['reminder' => $type, 'date' => $date, 'recipients' => $delivered],
                    ]);
                    $sent += $delivered;
                }
            }
        }

        $this->info('CRM follow-up reminder emails sent: '.$sent);

        return self::SUCCESS;
    }
}
