<?php

namespace App\Support;

use App\Mail\JourneyMeetingMail;
use App\Models\CrmJourneyPlan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The two reminders every meeting gets after the email sent when it was
 * booked: one the day before, one on the day. Each goes to the people listed
 * on the meeting and to the student's counsellor.
 *
 * Run often (every 15 minutes); each pass sends only what has come due and
 * not yet gone, so a pass that is late or missed catches up on the next one.
 * Each reminder is sent once per meeting date — move the meeting and the new
 * date is reminded afresh.
 */
final class MeetingReminders
{
    /** @return array{tomorrow: int, today: int, emails: int} */
    public static function send(?CarbonImmutable $now = null): array
    {
        $out = ['tomorrow' => 0, 'today' => 0, 'emails' => 0];
        if (! config('crm.email.enabled')) {
            return $out;
        }
        $tz = (string) config('journey.reminders.timezone', 'Asia/Kolkata');
        $now = ($now ?? CarbonImmutable::now())->setTimezone($tz);

        CrmJourneyPlan::query()->whereNotNull('meetings')->with(['lead.assignee'])->chunkById(100, function ($plans) use ($now, &$out): void {
            foreach ($plans as $plan) {
                foreach ($plan->meetings ?? [] as $raw) {
                    if (! is_array($raw) || empty($raw['key'])) {
                        continue;
                    }
                    $kind = self::due($raw, $now);
                    if ($kind === null) {
                        continue;
                    }
                    $sent = self::deliver($plan, (string) $raw['key'], $kind, $now);
                    if ($sent !== null) {
                        $out[$kind]++;
                        $out['emails'] += $sent;
                    }
                }
            }
        });

        return $out;
    }

    /**
     * Which reminder, if any, this meeting is owed right now.
     *
     * @return 'tomorrow'|'today'|null
     */
    public static function due(array $m, CarbonImmutable $now): ?string
    {
        if (! empty($m['done']) || ! ($m['remind'] ?? true) || ! JourneyPlanner::isDate($m['date'] ?? null)) {
            return null;
        }
        $tz = $now->getTimezone();
        $date = CarbonImmutable::createFromFormat('Y-m-d', $m['date'], $tz)->startOfDay();
        $time = preg_match('/^\d{2}:\d{2}$/', (string) ($m['time'] ?? '')) ? $m['time'] : null;
        $start = $time ? CarbonImmutable::createFromFormat('Y-m-d H:i', $m['date'].' '.$time, $tz) : null;
        $sent = CrmJourneyPlan::remindersSent($m);
        // Booked today: the booking email has only just gone; no reminder on top.
        $bookedOn = isset($m['createdAt']) ? CarbonImmutable::parse($m['createdAt'])->setTimezone($tz)->toDateString() : null;
        $bookedToday = $bookedOn === $now->toDateString();

        $today = $now->toDateString();
        if ($today === $date->subDay()->toDateString()) {
            $at = self::at($date->subDay(), (string) config('journey.reminders.day_before_at', '09:00'));

            return ! isset($sent['tomorrow']) && ! $bookedToday && $now->gte($at) ? 'tomorrow' : null;
        }
        if ($today === $date->toDateString()) {
            $at = self::at($date, (string) config('journey.reminders.same_day_at', '08:00'));
            if ($start) {
                $early = $start->subMinutes((int) config('journey.reminders.same_day_lead_minutes', 120));
                $at = $early->lt($at) ? $early->max($date) : $at;
            }
            $over = $start ? $now->gte($start) : false;

            return ! isset($sent['today']) && ! $bookedToday && ! $over && $now->gte($at) ? 'today' : null;
        }

        return null;
    }

    /**
     * Claim the reminder on the meeting (so no second pass sends it too),
     * then mail it. Returns how many emails left, or null if it was no
     * longer owed.
     */
    private static function deliver(CrmJourneyPlan $plan, string $key, string $kind, CarbonImmutable $now): ?int
    {
        $meeting = DB::transaction(function () use ($plan, $key, $kind, $now): ?array {
            $locked = CrmJourneyPlan::query()->lockForUpdate()->find($plan->id);
            $list = $locked?->meetings ?? [];
            foreach ($list as &$m) {
                if (($m['key'] ?? null) !== $key) {
                    continue;
                }
                if (self::due($m, $now) !== $kind) {
                    return null;
                }
                $m['reminders'] = (array) ($m['reminders'] ?? []);
                $m['reminders'][$kind] = ['for' => $m['date'], 'at' => $now->toIso8601String()];
                $locked->forceFill(['meetings' => $list])->save();

                return collect($locked->meetingRows())->firstWhere('key', $key);
            }

            return null;
        });
        if ($meeting === null) {
            return null;
        }

        $lead = $plan->lead;
        $to = $meeting['emails'];
        $counsellor = $lead?->assignee;
        if ($counsellor && filter_var((string) $counsellor->email, FILTER_VALIDATE_EMAIL)) {
            $to[] = strtolower((string) $counsellor->email);
        }
        $to = array_values(array_unique($to));
        $sent = 0;
        foreach ($to as $address) {
            try {
                // One mail per person, so nobody sees anyone else's address.
                Mail::mailer((string) config('crm.email.mailer'))->to($address)->send(new JourneyMeetingMail($meeting, (string) $lead?->name, $counsellor?->name, false, $kind));
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Journey meeting reminder failed', ['plan' => $plan->id, 'meeting' => $key, 'kind' => $kind, 'to' => $address, 'error' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    private static function at(CarbonImmutable $day, string $hhmm): CarbonImmutable
    {
        [$h, $i] = array_map('intval', explode(':', $hhmm) + [1 => 0]);

        return $day->setTime($h, $i);
    }
}
