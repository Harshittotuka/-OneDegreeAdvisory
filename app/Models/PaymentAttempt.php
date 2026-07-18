<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    protected $fillable = [
        'crm_lead_id',
        'request_token',
        'session_hash',
        'page_slug',
        'block_id',
        'option_index',
        'item_name',
        'amount',
        'currency',
        'theme_color',
        'customer_name',
        'customer_email',
        'customer_phone',
        'razorpay_order_id',
        'razorpay_payment_id',
        'status',
        'failure_reason',
        'paid_at',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    protected $hidden = [
        'session_hash',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'option_index' => 'integer',
            'paid_at' => 'datetime',
        ];
    }
}
