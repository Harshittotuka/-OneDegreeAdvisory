<?php

namespace App\Models;

use App\Support\JourneyPlanner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One enrolled student's journey planner: the Core Journey plus one
 * CrmJourneyApplication per university. See App\Support\JourneyPlanner.
 */
class CrmJourneyPlan extends Model
{
    protected $fillable = [
        'crm_lead_id', 'level', 'intake', 'focus', 'core', 'custom_tasks', 'custom_stages',
        'team', 'roles', 'deadlines', 'meetings', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'core' => 'array', 'custom_tasks' => 'array', 'custom_stages' => 'array',
            'team' => 'array', 'roles' => 'array', 'deadlines' => 'array', 'meetings' => 'array',
        ];
    }

    /** The plan for an enrolled student, created from the template the first time it is asked for. */
    public static function forLead(CrmLead $lead, ?CrmUser $creator = null): self
    {
        return static::query()->firstOrCreate(
            ['crm_lead_id' => $lead->id],
            ['core' => JourneyPlanner::freshCore(), 'intake' => $lead->intake ?: null, 'focus' => $lead->course_interest ?: null, 'created_by' => $creator?->id],
        );
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CrmJourneyApplication::class, 'plan_id')->orderBy('position')->orderBy('id');
    }

    /** Files and essays on this plan, newest first. */
    public function documents(): HasMany
    {
        return $this->hasMany(CrmJourneyDocument::class, 'plan_id')->latest('updated_at')->latest('id');
    }

    /**
     * The tasks a counsellor added to this student's journey, keyed like the
     * standard ones and shaped the same way, with `phase` and `custom` added.
     *
     * @return array<string, array<string, mixed>>
     */
    public function customDefinitions(): array
    {
        $phases = $this->phaseKeys();
        $out = [];
        foreach ($this->custom_tasks ?? [] as $t) {
            if (! is_array($t) || empty($t['key']) || ! in_array($t['phase'] ?? null, $phases, true)) {
                continue;
            }
            $out[$t['key']] = [
                'key' => $t['key'], 'name' => (string) ($t['name'] ?? 'Task'), 'desc' => (string) ($t['desc'] ?? ''),
                'owner' => in_array($t['owner'] ?? null, $this->ownerValues(), true) ? $t['owner'] : 'Student',
                'docs' => (string) (($t['docs'] ?? '') ?: 'None'), 'inc' => true, 'phase' => $t['phase'], 'custom' => true,
            ];
        }

        return $out;
    }

    /**
     * The stages a counsellor added, each {key, name, timeline, after}; `after`
     * is the standard stage it follows, or null for the end of the journey.
     *
     * @return list<array{key: string, name: string, timeline: string, after: ?string}>
     */
    public function customStages(): array
    {
        $standard = array_column(JourneyPlanner::phases(), 'key');
        $out = [];
        foreach ($this->custom_stages ?? [] as $st) {
            if (! is_array($st) || empty($st['key'])) {
                continue;
            }
            $out[] = [
                'key' => $st['key'], 'name' => (string) ($st['name'] ?? 'Stage'), 'timeline' => (string) ($st['timeline'] ?? ''),
                'after' => in_array($st['after'] ?? null, $standard, true) ? $st['after'] : null,
            ];
        }

        return $out;
    }

    /** Every stage key on this plan: ODA's seven plus the added ones. @return list<string> */
    public function phaseKeys(): array
    {
        return array_merge(array_column(JourneyPlanner::phases(), 'key'), array_column($this->customStages(), 'key'));
    }

    /**
     * The journey's stages in order: ODA's seven with each added stage placed
     * after the stage it follows (or at the end), with no tasks filled in.
     * Standard stages carry `custom: false`.
     *
     * @return list<array<string, mixed>>
     */
    public function orderedPhases(): array
    {
        $added = $this->customStages();
        $asPhase = fn (array $st): array => [
            'key' => $st['key'], 'name' => $st['name'], 'short' => Str::limit($st['name'], 22, '…'),
            'timeline' => $st['timeline'] !== '' ? $st['timeline'] : 'Added by your counsellor', 'activities' => [], 'custom' => true, 'after' => $st['after'],
        ];
        $out = [];
        foreach (JourneyPlanner::phases() as $p) {
            $out[] = $p + ['custom' => false];
            foreach ($added as $st) {
                if ($st['after'] === $p['key']) {
                    $out[] = $asPhase($st);
                }
            }
        }
        foreach ($added as $st) {
            if ($st['after'] === null) {
                $out[] = $asPhase($st);
            }
        }

        return $out;
    }

    /* ------------------------------------------------------------ team, deadlines, meetings */

    /**
     * Who is on this student's file. Stored rows are read back against the
     * shape the page expects, so nothing half-written can break the screen.
     *
     * @return list<array{key: string, role: string, name: string, contact: string, external: bool}>
     */
    public function teamMembers(): array
    {
        $roles = $this->teamRoles();
        $out = [];
        foreach ($this->team ?? [] as $m) {
            if (! is_array($m) || empty($m['key']) || trim((string) ($m['name'] ?? '')) === '') {
                continue;
            }
            $out[] = [
                'key' => (string) $m['key'],
                'role' => in_array($m['role'] ?? null, $roles, true) ? $m['role'] : $roles[0],
                'name' => (string) $m['name'],
                'contact' => (string) ($m['contact'] ?? ''),
                'external' => (bool) ($m['external'] ?? false),
            ];
        }

        return $out;
    }

    /**
     * Everything a task on this plan may be owned by: ODA's standard roles,
     * and each person named on the file.
     *
     * @return list<string>
     */
    public function ownerValues(): array
    {
        return array_merge(JourneyPlanner::OWNERS, array_map(
            fn (array $m) => 'member:'.$m['key'],
            $this->teamMembers(),
        ));
    }

    /**
     * The designations a member can hold: ODA's standard list plus any this
     * counsellor added for this student.
     *
     * @return list<string>
     */
    public function teamRoles(): array
    {
        $extra = array_values(array_filter(
            array_map(fn ($r) => is_string($r) ? trim($r) : '', $this->roles ?? []),
            fn (string $r) => $r !== '' && ! in_array($r, JourneyPlanner::TEAM_ROLES, true),
        ));

        return array_values(array_unique(array_merge(JourneyPlanner::TEAM_ROLES, $extra)));
    }

    /**
     * Every date on this plan that isn't a task's own target: the universities'
     * dates and ODA's internal ones, earliest first.
     *
     * @return list<array{key: string, kind: string, what: string, who: string, date: string}>
     */
    public function deadlineRows(): array
    {
        $out = [];
        foreach ($this->deadlines ?? [] as $d) {
            if (! is_array($d) || empty($d['key']) || ! JourneyPlanner::isDate($d['date'] ?? null)) {
                continue;
            }
            $out[] = [
                'key' => (string) $d['key'],
                'kind' => ($d['kind'] ?? null) === 'uni' ? 'uni' : 'own',
                'what' => (string) ($d['what'] ?? 'Deadline'),
                'who' => (string) ($d['who'] ?? ''),
                'date' => (string) $d['date'],
            ];
        }
        usort($out, fn (array $a, array $b) => $a['date'] <=> $b['date']);

        return $out;
    }

    /**
     * Meetings and calls booked on this plan, earliest first.
     *
     * `emails` are the people the join link is sent to; `phone` is the number
     * for a call. `sentAt` is when the link last went out, so the page can say
     * so rather than making the counsellor guess.
     *
     * @return list<array{key: string, title: string, date: string, time: string, minutes: int, mode: string, who: string, emails: list<string>, phone: string, link: string, notes: string, done: bool, sentAt: ?string}>
     */
    public function meetingRows(): array
    {
        $out = [];
        foreach ($this->meetings ?? [] as $m) {
            if (! is_array($m) || empty($m['key']) || ! JourneyPlanner::isDate($m['date'] ?? null)) {
                continue;
            }
            $out[] = [
                'key' => (string) $m['key'],
                'title' => (string) ($m['title'] ?? 'Meeting'),
                'date' => (string) $m['date'],
                'time' => preg_match('/^\d{2}:\d{2}$/', (string) ($m['time'] ?? '')) ? $m['time'] : '',
                'minutes' => max(5, min(480, (int) ($m['minutes'] ?? 45))),
                'mode' => in_array($m['mode'] ?? null, JourneyPlanner::MEETING_MODES, true) ? $m['mode'] : JourneyPlanner::MEETING_MODES[0],
                'who' => (string) ($m['who'] ?? ''),
                'emails' => self::emailList($m['emails'] ?? null),
                'phone' => (string) ($m['phone'] ?? ''),
                'link' => (string) ($m['link'] ?? ''),
                'notes' => (string) ($m['notes'] ?? ''),
                'done' => (bool) ($m['done'] ?? false),
                'sentAt' => is_string($m['sentAt'] ?? null) ? $m['sentAt'] : null,
            ];
        }
        usort($out, fn (array $a, array $b) => [$a['date'], $a['time']] <=> [$b['date'], $b['time']]);

        return $out;
    }

    /**
     * Addresses a meeting link goes to. Stored as a list, but a string of
     * comma-separated addresses is read too, so a row saved by hand still
     * comes back usable.
     *
     * @return list<string>
     */
    public static function emailList(mixed $value): array
    {
        $parts = is_array($value) ? $value : preg_split('/[,;\s]+/', (string) $value);

        return array_values(array_unique(array_filter(
            array_map(fn ($e) => mb_strtolower(trim((string) $e)), $parts ?: []),
            fn (string $e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL) !== false,
        )));
    }

    /** ODA's standard core tasks plus this plan's own. @return array<string, array<string, mixed>> */
    public function coreDefinitions(): array
    {
        return JourneyPlanner::coreDefinitions() + $this->customDefinitions();
    }

    /** @return array<string, array<string, mixed>> */
    public function coreState(): array
    {
        return JourneyPlanner::normalise($this->core ?? [], $this->coreDefinitions());
    }
}
