<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmWebsiteSubmission extends Model
{
    protected $fillable = [
        'crm_lead_id', 'external_id', 'source', 'source_label', 'degree',
        'sections', 'meta', 'ip_address', 'user_agent', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'meta' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }
}
