<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One line of a plan's "Recent changes". Written through App\Support\JourneyLog. */
class CrmJourneyChange extends Model
{
    protected $fillable = ['plan_id', 'actor_type', 'actor_id', 'actor_name', 'section', 'subject', 'what', 'internal'];

    protected function casts(): array
    {
        return ['internal' => 'boolean', 'actor_id' => 'integer'];
    }
}
