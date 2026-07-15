<?php

namespace App\Services;

use App\Models\CrmAuditLog;
use App\Models\CrmUser;
use Illuminate\Http\Request;

class CrmAuditLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function record(Request $request, CrmUser $actor, string $event, string $description, array $context = []): CrmAuditLog
    {
        return CrmAuditLog::query()->create([
            'crm_user_id' => $actor->id,
            'crm_lead_id' => $context['crm_lead_id'] ?? null,
            'event' => $event,
            'subject_type' => $context['subject_type'] ?? null,
            'subject_id' => $context['subject_id'] ?? null,
            'subject_label' => $context['subject_label'] ?? null,
            'description' => $description,
            'changes' => empty($context['changes']) ? null : $context['changes'],
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);
    }
}
