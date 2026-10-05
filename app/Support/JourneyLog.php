<?php

namespace App\Support;

use App\Models\CrmJourneyChange;
use App\Models\CrmJourneyPlan;
use App\Models\CrmUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The plan's "Recent changes": one line per thing someone changed, with who
 * and when.
 *
 * A burst of edits to the same thing by the same person — a status, then a
 * date, then a note, a few seconds apart — reads as one line, not three, so
 * the list stays a list of events rather than of keystrokes. Writing here
 * never stops the change itself from saving.
 */
final class JourneyLog
{
    /** Edits to the same thing within this many seconds join one line. */
    private const MERGE_SECONDS = 120;

    /** How many lines the page carries. */
    public const SHOWN = 50;

    /**
     * @param  CrmUser|null  $user  the team member, or null for the student
     */
    public static function record(CrmJourneyPlan $plan, ?CrmUser $user, string $section, string $subject, string $what, bool $internal = false): void
    {
        $what = trim($what);
        if ($what === '') {
            return;
        }
        try {
            $actor = $user
                ? ['actor_type' => 'team', 'actor_id' => $user->id, 'actor_name' => Str::limit((string) $user->name, 120, '')]
                : ['actor_type' => 'student', 'actor_id' => null, 'actor_name' => Str::limit((string) ($plan->lead?->name ?: 'Student'), 120, '')];
            $subject = Str::limit(trim($subject) ?: 'Plan', 190, '…');
            $section = Str::limit($section, 60, '');

            $last = CrmJourneyChange::query()->where('plan_id', $plan->id)->latest('id')->first();
            if ($last && $last->actor_type === $actor['actor_type'] && $last->actor_id === $actor['actor_id']
                && $last->section === $section && $last->subject === $subject
                && $last->updated_at?->isAfter(now()->subSeconds(self::MERGE_SECONDS))) {
                $last->forceFill(['what' => self::merge($last->what, $what), 'internal' => $last->internal && $internal])->save();

                return;
            }

            CrmJourneyChange::query()->create($actor + [
                'plan_id' => $plan->id,
                'section' => $section,
                'subject' => $subject,
                'what' => Str::limit($what, 300, '…'),
                'internal' => $internal,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Journey change log failed', ['plan' => $plan->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * The latest lines, as this reader sees them.
     *
     * @param  'counsellor'|'partner'|'student'  $mode
     * @return list<array<string, mixed>>
     */
    public static function forPlan(CrmJourneyPlan $plan, string $mode): array
    {
        return CrmJourneyChange::query()
            ->where('plan_id', $plan->id)
            ->when($mode !== 'counsellor', fn ($q) => $q->where('internal', false))
            ->latest('id')->limit(self::SHOWN)->get()
            ->map(fn (CrmJourneyChange $c) => [
                'id' => $c->id,
                'section' => $c->section,
                'subject' => $c->subject,
                'what' => $c->what,
                'who' => $c->actor_type === 'student' ? ($mode === 'student' ? 'You' : $c->actor_name.' (student)') : $c->actor_name,
                'byStudent' => $c->actor_type === 'student',
                'at' => $c->updated_at?->toIso8601String(),
            ])->all();
    }

    /**
     * What changed on one activity, in words. Only fields that actually
     * moved are named.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  callable(string): string  $ownerName
     */
    public static function describeActivity(array $before, array $after, callable $ownerName): string
    {
        $parts = [];
        if (($before['inc'] ?? null) !== ($after['inc'] ?? null)) {
            $parts[] = ! empty($after['inc']) ? 'Included' : 'Excluded';
        }
        if (($before['status'] ?? null) !== ($after['status'] ?? null)) {
            $parts[] = 'Status: '.$after['status'];
        }
        if (($before['owner'] ?? null) !== ($after['owner'] ?? null)) {
            $parts[] = 'Owner: '.$ownerName((string) $after['owner']);
        }
        if (($before['target'] ?? null) !== ($after['target'] ?? null)) {
            $parts[] = $after['target'] ? 'Target date: '.self::date($after['target']) : 'Target date cleared';
        }
        if (($before['done'] ?? null) !== ($after['done'] ?? null) && ($before['status'] ?? null) === ($after['status'] ?? null)) {
            $parts[] = $after['done'] ? 'Completion date: '.self::date($after['done']) : 'Completion date cleared';
        }
        if (($before['notes'] ?? '') !== ($after['notes'] ?? '')) {
            $parts[] = 'Notes updated';
        }

        return implode(' · ', $parts);
    }

    public static function date(?string $iso): string
    {
        return $iso ? date('j M Y', strtotime($iso)) : '';
    }

    /** Join a new part onto a line, replacing an earlier part about the same field. */
    private static function merge(string $old, string $new): string
    {
        $parts = array_values(array_filter(array_map('trim', explode(' · ', $old))));
        foreach (array_filter(array_map('trim', explode(' · ', $new))) as $part) {
            $label = Str::before($part, ':');
            $parts = array_values(array_filter($parts, fn (string $p) => Str::before($p, ':') !== $label
                && ! (in_array($p, ['Included', 'Excluded'], true) && in_array($part, ['Included', 'Excluded'], true))));
            $parts[] = $part;
        }

        return Str::limit(implode(' · ', $parts), 300, '…');
    }
}
