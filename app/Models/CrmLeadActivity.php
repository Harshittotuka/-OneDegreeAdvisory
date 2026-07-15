<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLeadActivity extends Model
{
    protected $fillable = ['crm_lead_id', 'crm_user_id', 'type', 'body', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'crm_user_id');
    }
}
