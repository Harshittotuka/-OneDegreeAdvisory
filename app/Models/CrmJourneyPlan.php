<?php

namespace App\Models;

use App\Support\JourneyPlanner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One enrolled student's journey planner: the Core Journey plus one
 * CrmJourneyApplication per university. See App\Support\JourneyPlanner.
 */
class CrmJourneyPlan extends Model
{
    protected $fillable = ['crm_lead_id', 'level', 'intake', 'focus', 'core', 'custom_tasks', 'custom_stages', 'created_by'];

    protected function casts(): array
    {
        return ['core' => 'array', 'custom_tasks' => 'array', 'custom_stages' => 'array'];
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
                'owner' => in_array($t['owner'] ?? null, JourneyPlanner::OWNERS, true) ? $t['owner'] : 'Student',
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
            'key' => $st['key'], 'name' => $st['name'], 'short' => \Illuminate\Support\Str::limit($st['name'], 22, '…'),
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
