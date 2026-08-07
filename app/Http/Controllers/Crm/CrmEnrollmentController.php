<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmUser;
use App\Models\PaymentAttempt;
use App\Services\WebsiteLeadManager;
use App\Support\CrmFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmEnrollmentController extends Controller
{
    public const STATUSES = ['paid' => 'Paid', 'order_created' => 'Awaiting payment', 'order_creating' => 'Starting payment', 'payment_failed' => 'Payment failed', 'order_failed' => 'Order failed'];

    public function __construct(private WebsiteLeadManager $leads) {}

    public function update(Request $request, PaymentAttempt $attempt): RedirectResponse
    {
        $this->guard($request);
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(self::STATUSES))]]);
        $attempt->update(['status' => $data['status'], 'paid_at' => $data['status'] === 'paid' ? ($attempt->paid_at ?: now()) : $attempt->paid_at]);
        $this->leads->syncPaymentStatus($attempt->fresh());

        return back()->with('status', 'Enrollment payment status updated.');
    }

    public function destroy(Request $request, PaymentAttempt $attempt): RedirectResponse
    {
        $this->guard($request);
        $attempt->delete();

        return back()->with('status', 'Enrollment transaction deleted; the CRM lead remains available.');
    }

    public function export(Request $request): StreamedResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        $query = PaymentAttempt::query()->with('lead.assignee')->latest();
        if (! $user->isSuperAdmin()) {
            $query->whereHas('lead', fn ($lead) => $lead->visibleTo($user));
        }
        // Same multi-value filters as the list this exports, so the CSV is the
        // rows the user is looking at rather than a differently-filtered set.
        if ($statuses = CrmFilter::values($request, 'payment_status', self::STATUSES)) $query->whereIn('status', $statuses);
        if ($pages = CrmFilter::raw($request, 'enrollment_source')) $query->whereIn('page_slug', $pages);
        if ($plans = CrmFilter::raw($request, 'enrollment_plan')) $query->whereIn('item_name', $plans);
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($q) => $q->where('customer_name', 'like', "%{$search}%")->orWhere('customer_email', 'like', "%{$search}%")->orWhere('customer_phone', 'like', "%{$search}%")->orWhere('item_name', 'like', "%{$search}%")->orWhere('razorpay_payment_id', 'like', "%{$search}%")->orWhere('razorpay_order_id', 'like', "%{$search}%"));
        }
        $rows = $query->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Created', 'Lead ID', 'Name', 'Email', 'Phone', 'Source page', 'Plan', 'Amount', 'Currency', 'Status', 'Payment ID', 'Order ID', 'Failure reason', 'Paid at']);
            foreach ($rows as $row) fputcsv($out, [$row->created_at, $row->lead?->lead_number, $row->customer_name, $row->customer_email, $row->customer_phone, $row->page_slug, $row->item_name, number_format($row->amount / 100, 2, '.', ''), $row->currency, $row->status, $row->razorpay_payment_id, $row->razorpay_order_id, $row->failure_reason, $row->paid_at]);
            fclose($out);
        }, 'crm-enrollments-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function guard(Request $request): void
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        abort_unless($user->isSuperAdmin(), 403);
    }
}
