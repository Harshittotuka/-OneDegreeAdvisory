<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmPartnerCode;
use App\Models\CrmUser;
use App\Services\CrmAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The Partner codes tab: the referral companies we hand a tracking code to.
 *
 * Neither creating nor editing is here. A partner is one thing — a login, a
 * company, a code — and the Team screen holds all of it (CrmUserController),
 * because two forms for one company was how the same partner ended up entered
 * twice, under two spellings, with two email addresses. What is left is what is
 * true of the link rather than the partner: pause it, remove it, export it.
 *
 * Super admin only, for the same reason Team management is — a code decides
 * which outside address gets emailed a student's enquiry, so issuing one is an
 * administrative act rather than day-to-day lead work.
 */
class CrmPartnerCodeController extends Controller
{
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

        $auditLogger->record($request, $admin, 'partner_code_access_changed', ($partnerCode->is_active ? 'Reactivated' : 'Paused').' partner code '.$partnerCode->label().'.',
            $partnerCode->auditSubject(['before' => ['is_active' => $wasActive], 'after' => ['is_active' => $partnerCode->is_active]]));

        return back()->with('status', $partnerCode->is_active
            ? $partnerCode->company_name."'s link is live again."
            : $partnerCode->company_name."'s link is paused — new submissions carrying ".$partnerCode->code.' are recorded without a partner.');
    }

    public function destroy(Request $request, CrmPartnerCode $partnerCode, CrmAuditLogger $auditLogger): RedirectResponse
    {
        $admin = $this->guard($request);
        $attributed = $partnerCode->leads()->count();
        $label = $partnerCode->label();

        $auditLogger->record($request, $admin, 'partner_code_deleted', 'Deleted partner code '.$label.'.',
            $partnerCode->auditSubject([
                'before' => $partnerCode->only([...CrmPartnerCode::TRACKED_FIELDS, 'is_active']),
                'leads_unattributed' => $attributed,
            ]));

        $company = $partnerCode->company_name;
        // Removing the link never removes the login. The account is a person who
        // may still be watching their students; ending the referral arrangement
        // and closing their workspace are two decisions, taken on two screens.
        $account = $partnerCode->account;
        // partner_code_id is nullOnDelete, so the leads survive and simply stop
        // naming a partner. Pausing keeps the link's history; this is the clean
        // removal for a company we never worked with.
        $partnerCode->delete();

        return back()->with('status', $company.' was removed.'.($attributed
            ? ' '.$attributed.' lead'.($attributed === 1 ? '' : 's').' no longer name a partner code.'
            : '').($account
                ? ' '.$account->name.' can still sign in — remove the account on the Team screen to close it.'
                : ''));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->guard($request);
        $rows = CrmPartnerCode::query()->with('account')->withCount('leads')->orderBy('company_name')->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Company name', 'Code', 'Email', 'Phone', 'Contact name', 'Company link', 'Signs in', 'Status', 'Leads', 'Profiler link', 'Created']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->company_name, $row->code, $row->email, $row->phone, $row->contact_name, $row->company_link,
                    $row->account ? $row->account->name.' ('.$row->account->partnerAccessLabel().')' : 'No workspace account',
                    $row->is_active ? 'Active' : 'Paused', $row->leads_count, $row->profilerUrl(),
                    $row->created_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, 'crm-partner-codes-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function guard(Request $request): CrmUser
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        abort_unless($user->isSuperAdmin(), 403);

        return $user;
    }
}
