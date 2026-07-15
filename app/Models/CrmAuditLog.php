<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmAuditLog extends Model
{
    protected $fillable = [
        'crm_user_id', 'crm_lead_id', 'event', 'subject_type', 'subject_id',
        'subject_label', 'description', 'changes', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['changes' => 'array'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'crm_user_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id')->withTrashed();
    }
}
