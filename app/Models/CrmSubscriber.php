<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmSubscriber extends Model
{
    protected $fillable = [
        'email', 'source', 'status', 'subscribed_at', 'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }
}
