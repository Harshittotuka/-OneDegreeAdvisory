<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Admin portal — Enrollments. A read-only view of everyone who has started or
 * completed a plan payment (the payment_attempts table), with revenue stats.
 * Lives behind the same cms.auth login as the CMS; chosen from the portal picker.
 */
class EnrollmentsController extends Controller
{
    private const FILTERABLE = ['paid', 'order_created', 'order_creating', 'payment_failed', 'order_failed'];

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

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $q = trim((string) $request->query('q', ''));

        $query = PaymentAttempt::query()->latest();
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
            'stats' => [
                'total' => PaymentAttempt::count(),
                'paid' => PaymentAttempt::where('status', 'paid')->count(),
                'revenue' => (int) PaymentAttempt::where('status', 'paid')->sum('amount'),
                'failed' => PaymentAttempt::whereIn('status', ['payment_failed', 'order_failed'])->count(),
            ],
        ]);
    }
}
