<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentAttempt;
use App\Support\TestPrepCompareStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin portal — Enrollments. A read-only view of everyone who has started or
 * completed a plan payment (the payment_attempts table), with revenue stats.
 * Lives behind the same cms.auth login as the CMS; chosen from the portal picker.
 */
class EnrollmentsController extends Controller
{
    private const FILTERABLE = ['paid', 'order_created', 'order_creating', 'payment_failed', 'order_failed'];

    /** Statuses an admin may set manually from the transactions table. */
    public const EDITABLE_STATUSES = [
        'paid' => 'Paid',
        'order_created' => 'Awaiting payment',
        'payment_failed' => 'Payment failed',
        'order_failed' => 'Order failed',
    ];

    /** Admin portal landing — a payments/enrollments overview. */
    public function overview(): View
    {
        $total = PaymentAttempt::count();
        $paidCount = PaymentAttempt::where('status', 'paid')->count();

        return view('admin.enrollments.dashboard', [
            'portal' => 'admin',
            'stats' => [
                'revenue' => (int) PaymentAttempt::where('status', 'paid')->sum('amount'),
                'month_revenue' => (int) PaymentAttempt::where('status', 'paid')
                    ->whereYear('paid_at', now()->year)
                    ->whereMonth('paid_at', now()->month)
                    ->sum('amount'),
                'paid' => $paidCount,
                'total' => $total,
                'pending' => PaymentAttempt::whereIn('status', ['order_created', 'order_creating'])->count(),
                'failed' => PaymentAttempt::whereIn('status', ['payment_failed', 'order_failed'])->count(),
                'conversion' => $total > 0 ? (int) round($paidCount / $total * 100) : 0,
            ],
            'byPlan' => PaymentAttempt::where('status', 'paid')
                ->selectRaw('item_name, COUNT(*) as cnt, SUM(amount) as total')
                ->groupBy('item_name')
                ->orderByDesc('total')
                ->limit(8)
                ->get(),
            'recent' => PaymentAttempt::latest()->limit(8)->get(),
        ]);
    }

    /** All enrollments across the whole site. */
    public function index(Request $request): View
    {
        return $this->renderList($request, null, [
            'title' => 'Enrollments',
            'listRoute' => 'admin.enrollments.index',
            'match' => 'admin.enrollments.index',
            'showPage' => true,
            'intro' => null,
        ]);
    }

    /**
     * Enrollments from the Test-Prep "Compare & enrol" section only — scoped to
     * its sentinel page slug so those students are tracked separately.
     */
    public function testPrep(Request $request, TestPrepCompareStore $compare): View
    {
        return $this->renderList($request, TestPrepCompareStore::PAGE_SLUG, [
            'title' => 'Test Prep enrollments',
            'listRoute' => 'admin.enrollments.test-prep',
            'match' => 'admin.enrollments.test-prep',
            'showPage' => false, // every row is the same page here
            'intro' => 'Students who enrolled through the Test Preparation page’s “Compare & enrol” section.',
            // The program the student paid for is stored on each attempt's
            // item_name. Offer a "type" filter built from every program the CMS
            // currently lists AND every program name that appears in the data —
            // so a since-renamed/removed program still shows up as a choice.
            'typeNames' => $this->testPrepTypeNames($compare),
        ]);
    }

    /**
     * Distinct program names to offer as the Test-Prep "type" filter: the union
     * of the CMS's current programs (all of them, visible or not) and the
     * item_name of every recorded test-prep attempt (so old/renamed programs
     * are still filterable from the historical data). Sorted, case-insensitive.
     */
    private function testPrepTypeNames(TestPrepCompareStore $compare): array
    {
        $fromCms = array_map(
            fn (array $p) => trim((string) ($p['name'] ?? '')),
            $compare->get()['programs'] ?? []
        );

        $fromData = PaymentAttempt::query()
            ->where('page_slug', TestPrepCompareStore::PAGE_SLUG)
            ->whereNotNull('item_name')
            ->distinct()
            ->pluck('item_name')
            ->all();

        $names = array_filter(
            array_map('trim', array_merge($fromCms, $fromData)),
            fn (string $n) => $n !== ''
        );

        // De-dupe case-insensitively, keeping the first-seen casing, then sort.
        $unique = [];
        foreach ($names as $n) {
            $unique[mb_strtolower($n)] ??= $n;
        }
        $unique = array_values($unique);
        natcasesort($unique);

        return array_values($unique);
    }

    /**
     * Shared transactions table. When $pageSlug is given every query is scoped to
     * that page, so stats + list reflect only that section's payments.
     */
    private function renderList(Request $request, ?string $pageSlug, array $context): View
    {
        $status = (string) $request->query('status', '');
        $q = trim((string) $request->query('q', ''));
        // "type" filters by the exact program name stored on item_name; only
        // honoured on section-scoped lists that provide the choices.
        $type = trim((string) $request->query('type', ''));
        $typeNames = $context['typeNames'] ?? [];
        if ($type !== '' && ! in_array($type, $typeNames, true)) {
            $type = ''; // ignore a type that isn't one of the offered choices
        }

        $scoped = fn () => $pageSlug === null
            ? PaymentAttempt::query()
            : PaymentAttempt::query()->where('page_slug', $pageSlug);

        // Stats reflect the active type filter too, so the numbers match the list.
        $statScope = function () use ($scoped, $type) {
            $b = $scoped();
            if ($type !== '') {
                $b->where('item_name', $type);
            }

            return $b;
        };

        $query = $scoped()->latest();
        if ($type !== '') {
            $query->where('item_name', $type);
        }
        if (in_array($status, self::FILTERABLE, true)) {
            $query->where('status', $status);
        }
        if ($q !== '') {
            $query->where(function ($w) use ($q): void {
                $w->where('customer_name', 'like', "%{$q}%")
                    ->orWhere('customer_email', 'like', "%{$q}%")
                    ->orWhere('customer_phone', 'like', "%{$q}%")
                    ->orWhere('item_name', 'like', "%{$q}%")
                    ->orWhere('razorpay_payment_id', 'like', "%{$q}%")
                    ->orWhere('razorpay_order_id', 'like', "%{$q}%");
            });
        }

        $attempts = $query->limit(500)->get();

        return view('admin.enrollments.index', [
            'portal' => 'admin',
            'attempts' => $attempts,
            'status' => $status,
            'q' => $q,
            'type' => $type,
            'editable' => self::EDITABLE_STATUSES,
            'listContext' => $context,
            'stats' => [
                'total' => $statScope()->count(),
                'paid' => $statScope()->where('status', 'paid')->count(),
                'revenue' => (int) $statScope()->where('status', 'paid')->sum('amount'),
                'failed' => $statScope()->whereIn('status', ['payment_failed', 'order_failed'])->count(),
            ],
        ]);
    }

    /** Manually set a transaction's status from the table. */
    public function updateStatus(Request $request, PaymentAttempt $attempt): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(self::EDITABLE_STATUSES))],
        ]);

        $attempt->status = $validated['status'];
        if ($validated['status'] === 'paid' && $attempt->paid_at === null) {
            $attempt->paid_at = now();
        }
        $attempt->save();

        return back()->with('status', 'Status updated to "'.self::EDITABLE_STATUSES[$validated['status']].'".');
    }

    /** Permanently delete a transaction record. */
    public function destroy(PaymentAttempt $attempt): RedirectResponse
    {
        $attempt->delete();

        return back()->with('status', 'Transaction deleted.');
    }
}
