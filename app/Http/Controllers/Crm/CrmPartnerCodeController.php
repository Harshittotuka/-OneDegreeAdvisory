<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmPartnerCode;
use App\Models\CrmUser;
use App\Services\CrmAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The Partner codes tab: the referral companies we hand a tracking code to.
 *
 * Super admin only, for the same reason Team management is — a code decides
 * which outside address gets emailed a student's enquiry, so issuing one is an
 * administrative act rather than day-to-day lead work.
 */
class CrmPartnerCodeController extends Controller
{
    public function store(Request $request, CrmAuditLogger $auditLogger): RedirectResponse
    {
        $admin = $this->guard($request);
        $data = $this->validated($request);

        $code = CrmPartnerCode::query()->create([...$data, 'is_active' => true, 'created_by' => $admin->id]);

        $auditLogger->record($request, $admin, 'partner_code_created', 'Created partner code '.$code->label().'.', [
            'subject_type' => 'partner_code',
            'subject_id' => $code->id,
            'subject_label' => $code->label(),
            'changes' => ['after' => $code->only(['company_name', 'code', 'email', 'phone', 'contact_name', 'company_link'])],
        ]);

        return back()
            ->with('status', $code->company_name.' can now share the link carrying code '.$code->code.'.')
            ->with('new_partner_code', $code->id);
    }

    public function update(Request $request, CrmPartnerCode $partnerCode, CrmAuditLogger $auditLogger): RedirectResponse
    {
        $admin = $this->guard($request);
        $data = $this->validated($request, $partnerCode);

        $before = $partnerCode->only(['company_name', 'code', 'email', 'phone', 'contact_name', 'company_link']);
        $partnerCode->update($data);
        $after = $partnerCode->only(array_keys($before));

        if ($after === $before) {
            return back()->with('status', 'No changes to save for '.$partnerCode->company_name.'.');
        }

        $auditLogger->record($request, $admin, 'partner_code_updated', 'Updated partner code '.$partnerCode->label().'.', [
            'subject_type' => 'partner_code',
            'subject_id' => $partnerCode->id,
            'subject_label' => $partnerCode->label(),
            'changes' => ['before' => $before, 'after' => $after],
        ]);

        // Changing the code retires the old link, which is worth saying out loud:
        // anything already printed or posted with it stops being attributed.
        $note = $before['code'] !== $after['code']
            ? ' The old link ('.$before['code'].') no longer names a partner — reshare the new one.'
            : '';

        return back()->with('status', $partnerCode->company_name.' was updated.'.$note);
    }

    /**
     * Switch a code off (or back on).
     *
     * Preferred over deleting: the leads it already brought in keep their
     * attribution, while the link stops notifying anyone and stops attributing
     * anything new.
     */
    public function toggle(Request $request, CrmPartnerCode $partnerCode, CrmAuditLogger $auditLogger): RedirectResponse
    {
        $admin = $this->guard($request);
        $wasActive = $partnerCode->is_active;
        $partnerCode->update(['is_active' => ! $wasActive]);

        $auditLogger->record($request, $admin, 'partner_code_access_changed', ($partnerCode->is_active ? 'Reactivated' : 'Paused').' partner code '.$partnerCode->label().'.', [
            'subject_type' => 'partner_code',
            'subject_id' => $partnerCode->id,
            'subject_label' => $partnerCode->label(),
            'changes' => ['before' => ['is_active' => $wasActive], 'after' => ['is_active' => $partnerCode->is_active]],
        ]);

        return back()->with('status', $partnerCode->is_active
            ? $partnerCode->company_name."'s link is live again."
            : $partnerCode->company_name."'s link is paused — new submissions carrying ".$partnerCode->code.' are recorded without a partner.');
    }

    public function destroy(Request $request, CrmPartnerCode $partnerCode, CrmAuditLogger $auditLogger): RedirectResponse
    {
        $admin = $this->guard($request);
        $attributed = $partnerCode->leads()->count();
        $label = $partnerCode->label();

        $auditLogger->record($request, $admin, 'partner_code_deleted', 'Deleted partner code '.$label.'.', [
            'subject_type' => 'partner_code',
            'subject_id' => $partnerCode->id,
            'subject_label' => $label,
            'changes' => [
                'before' => $partnerCode->only(['company_name', 'code', 'email', 'phone', 'contact_name', 'company_link', 'is_active']),
                'leads_unattributed' => $attributed,
            ],
        ]);

        $company = $partnerCode->company_name;
        // partner_code_id is nullOnDelete, so the leads survive and simply stop
        // naming a partner. Pausing keeps the link's history; this is the clean
        // removal for a company we never worked with.
        $partnerCode->delete();

        return back()->with('status', $company.' was removed.'.($attributed
            ? ' '.$attributed.' lead'.($attributed === 1 ? '' : 's').' no longer name a partner code.'
            : ''));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->guard($request);
        $rows = CrmPartnerCode::query()->withCount('leads')->orderBy('company_name')->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Company name', 'Code', 'Email', 'Phone', 'Contact name', 'Company link', 'Status', 'Leads', 'Profiler link', 'Created']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->company_name, $row->code, $row->email, $row->phone, $row->contact_name, $row->company_link,
                    $row->is_active ? 'Active' : 'Paused', $row->leads_count, $row->profilerUrl(),
                    $row->created_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, 'crm-partner-codes-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{company_name: string, code: string, email: string, phone: ?string, contact_name: ?string, company_link: ?string}
     */
    private function validated(Request $request, ?CrmPartnerCode $existing = null): array
    {
        // Normalised BEFORE validation, not after, so the character rule and the
        // uniqueness check both run against the one form a code is ever stored in.
        // Checking the raw value instead let "acme10" pass as unique against a
        // stored "ACME10" and then collide on the column's own unique index.
        $request->merge(['code' => CrmPartnerCode::normaliseCode($request->input('code'))]);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            // The code goes into a URL and is typed by hand, so it is held to
            // link-safe characters and compared upper-cased (see
            // CrmPartnerCode::normaliseCode) — "acme10" and "ACME10" are one code.
            'code' => [
                'required', 'string', 'max:40', 'regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/',
                Rule::unique('crm_partner_codes', 'code')->ignore($existing?->id),
            ],
            'email' => ['required', 'email', 'max:190', 'real_email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'company_link' => ['nullable', 'url', 'max:255'],
        ], [
            'code.regex' => 'A code can use letters, numbers, hyphens and underscores only — it travels in a link.',
            'code.unique' => 'Another partner already has this code.',
            'company_link.url' => 'Enter the full company website, including https://.',
        ]);

        return [
            'company_name' => trim($data['company_name']),
            'code' => CrmPartnerCode::normaliseCode($data['code']),
            'email' => mb_strtolower(trim($data['email'])),
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'contact_name' => trim((string) ($data['contact_name'] ?? '')) ?: null,
            'company_link' => trim((string) ($data['company_link'] ?? '')) ?: null,
        ];
    }

    private function guard(Request $request): CrmUser
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        abort_unless($user->isSuperAdmin(), 403);

        return $user;
    }
}
