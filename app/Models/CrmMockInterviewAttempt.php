<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmMockInterviewAttempt extends Model
{
    protected $fillable = [
        'invite_id', 'session_key', 'ip_address', 'user_agent',
        'questions_planned', 'questions_answered', 'overall_score',
        'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'questions_planned' => 'integer',
            'questions_answered' => 'integer',
            'overall_score' => 'float',
        ];
    }

    public function invite(): BelongsTo
    {
        return $this->belongsTo(CrmMockInterviewInvite::class, 'invite_id');
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }
}
