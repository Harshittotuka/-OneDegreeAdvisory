<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmSpamAttempt extends Model
{
    protected $fillable = ['source', 'ip_address', 'user_agent', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];
}
