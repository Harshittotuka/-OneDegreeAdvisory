<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A file or an essay stored on a student's journey plan. See App\Support\JourneyDocuments. */
class CrmJourneyDocument extends Model
{
    protected $fillable = [
        'plan_id', 'application_id', 'kind', 'title', 'category', 'body', 'status', 'feedback', 'feedback_by',
        'feedback_at', 'submitted_at', 'path', 'original_name', 'mime', 'size', 'by_student', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'by_student' => 'boolean', 'size' => 'integer',
            'feedback_at' => 'datetime', 'submitted_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CrmJourneyPlan::class, 'plan_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(CrmJourneyApplication::class, 'application_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'feedback_by');
    }

    public function isEssay(): bool
    {
        return $this->kind === 'essay';
    }

    public function wordCount(): int
    {
        $text = trim(strip_tags((string) $this->body));

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);
    }
}
