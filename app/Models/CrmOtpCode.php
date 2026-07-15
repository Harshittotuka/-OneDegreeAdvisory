<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmOtpCode extends Model
{
    protected $fillable = ['crm_user_id', 'code_hash', 'attempts', 'expires_at', 'used_at', 'ip_address', 'user_agent'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'crm_user_id');
    }
}
