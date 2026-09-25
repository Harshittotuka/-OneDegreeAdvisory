<?php

namespace App\Models;

use App\Support\JourneyPlanner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One university or programme block in a student's journey planner. */
class CrmJourneyApplication extends Model
{
    protected $fillable = ['plan_id', 'position', 'university', 'country', 'program', 'fit', 'offer_type', 'activities'];

    protected function casts(): array
    {
        return ['activities' => 'array', 'position' => 'integer'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CrmJourneyPlan::class, 'plan_id');
    }

    /** @return array<string, array<string, mixed>> */
    public function activityState(): array
    {
        return JourneyPlanner::normalise($this->activities ?? [], JourneyPlanner::applicationDefinitions());
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
            'acts' => $this->activityState(),
        ];
    }
}
