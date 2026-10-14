<?php

namespace App\Models;

use App\Support\JourneyPlanner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One university or programme block in a student's journey planner. */
class CrmJourneyApplication extends Model
{
    protected $fillable = [
        'plan_id', 'position', 'university', 'country', 'program', 'fit', 'offer_type', 'activities', 'custom_tasks',
        'tests_required', 'documents_required', 'requirements', 'deadline',
    ];

    protected function casts(): array
    {
        return ['activities' => 'array', 'custom_tasks' => 'array', 'position' => 'integer', 'deadline' => 'date'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CrmJourneyPlan::class, 'plan_id');
    }

    /**
     * The tasks and events a counsellor added to this university's checklist,
     * keyed like the standard ones and shaped the same way, with `group` (the
     * checklist group it sits in) and `custom` added.
     *
     * @return array<string, array<string, mixed>>
     */
    public function customDefinitions(): array
    {
        $groups = JourneyPlanner::applicationGroupKeys();
        $out = [];
        foreach ($this->custom_tasks ?? [] as $t) {
            if (! is_array($t) || empty($t['key'])) {
                continue;
            }
            $owner = $t['owner'] ?? null;
            $out[$t['key']] = [
                'key' => $t['key'], 'name' => (string) ($t['name'] ?? 'Task'), 'desc' => (string) ($t['desc'] ?? ''),
                'owner' => in_array($owner, JourneyPlanner::OWNERS, true) || JourneyPlanner::isMemberOwner($owner) ? $owner : 'Student',
                'docs' => (string) (($t['docs'] ?? '') ?: 'None'), 'inc' => true,
                'group' => in_array($t['group'] ?? null, $groups, true) ? $t['group'] : 'application',
                'custom' => true,
            ];
        }

        return $out;
    }

    /** ODA's standard checklist plus this university's own tasks. @return array<string, array<string, mixed>> */
    public function definitions(): array
    {
        return JourneyPlanner::applicationDefinitions() + $this->customDefinitions();
    }

    /** @return array<string, array<string, mixed>> */
    public function activityState(): array
    {
        return JourneyPlanner::normalise($this->activities ?? [], $this->definitions());
    }

    /** @return array<string, mixed> */
    public function toPlannerArray(): array
    {
        return [
            'id' => $this->id,
            'university' => $this->university,
            'country' => (string) $this->country,
            'program' => (string) $this->program,
            'fit' => (string) $this->fit,
            'offerType' => (string) $this->offer_type,
            // What this university asks for, as the counsellor recorded it.
            'tests' => (string) $this->tests_required,
            'docs' => (string) $this->documents_required,
            'requirements' => (string) $this->requirements,
            'deadline' => $this->deadline?->toDateString(),
            // The counsellor's own tasks on this university; their state is in `acts`.
            'tasks' => array_values($this->customDefinitions()),
            'acts' => $this->activityState(),
        ];
    }
}
