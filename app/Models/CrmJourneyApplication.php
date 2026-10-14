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
     * checklist group it sits in), `parent` and `custom` added.
     *
     * A task may sit under another — one of ODA's or one of these — as its
     * subtask, and a subtask may have subtasks of its own, as deep as the
     * counsellor likes. A subtask always sits in the group of the task at the
     * top of its branch. A parent that no longer exists, or a loop no page
     * could have made, leaves the task at the top of its own group.
     *
     * @return array<string, array<string, mixed>>
     */
    public function customDefinitions(): array
    {
        $groups = JourneyPlanner::applicationGroupKeys();
        $standardGroup = JourneyPlanner::applicationGroupOf();
        $rows = [];
        foreach ($this->custom_tasks ?? [] as $t) {
            if (is_array($t) && ! empty($t['key'])) {
                $rows[$t['key']] = $t;
            }
        }

        $parentOf = [];
        foreach ($rows as $key => $t) {
            $p = $t['parent'] ?? null;
            $parentOf[$key] = is_string($p) && $p !== $key && (isset($standardGroup[$p]) || isset($rows[$p])) ? $p : null;
        }
        $parentOf = JourneyPlanner::withoutLoops($parentOf);

        $out = [];
        foreach ($rows as $key => $t) {
            // The group is the one at the top of the branch.
            $top = $key;
            while ($parentOf[$top] !== null && isset($rows[$parentOf[$top]])) {
                $top = $parentOf[$top];
            }
            $group = $parentOf[$top] !== null ? $standardGroup[$parentOf[$top]] : ($rows[$top]['group'] ?? null);

            $owner = $t['owner'] ?? null;
            $out[$key] = [
                'key' => $key, 'name' => (string) ($t['name'] ?? 'Task'), 'desc' => (string) ($t['desc'] ?? ''),
                'owner' => in_array($owner, JourneyPlanner::OWNERS, true) || JourneyPlanner::isMemberOwner($owner) ? $owner : 'Student',
                'docs' => (string) (($t['docs'] ?? '') ?: 'None'), 'inc' => true,
                'group' => in_array($group, $groups, true) ? $group : 'application',
                'parent' => $parentOf[$key],
                'custom' => true,
            ];
        }

        return $out;
    }

    /**
     * Every added task under this one, at any depth, nearest first.
     *
     * @return list<string>
     */
    public function subtaskKeys(string $key): array
    {
        $children = [];
        foreach ($this->customDefinitions() as $def) {
            if ($def['parent'] !== null) {
                $children[$def['parent']][] = $def['key'];
            }
        }
        $out = [];
        $queue = $children[$key] ?? [];
        while ($queue !== []) {
            $next = array_shift($queue);
            if (! in_array($next, $out, true)) {
                $out[] = $next;
                array_push($queue, ...($children[$next] ?? []));
            }
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
