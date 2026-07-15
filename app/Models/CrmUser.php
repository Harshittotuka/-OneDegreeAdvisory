<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmUser extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'role', 'is_active', 'created_by', 'last_login_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_login_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CrmLead::class, 'assigned_to');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
