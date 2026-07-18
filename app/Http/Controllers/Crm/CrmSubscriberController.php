<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmSubscriber;
use App\Models\CrmUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmSubscriberController extends Controller
{
    public function update(Request $request, CrmSubscriber $subscriber): RedirectResponse
    {
        $this->guard($request);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'unsubscribed'])]]);
        $subscriber->update([
            'status' => $data['status'],
            'unsubscribed_at' => $data['status'] === 'unsubscribed' ? ($subscriber->unsubscribed_at ?: now()) : null,
        ]);

        return back()->with('status', 'Subscription status updated.');
    }

    public function destroy(Request $request, CrmSubscriber $subscriber): RedirectResponse
    {
        $this->guard($request);
        $subscriber->delete();

        return back()->with('status', 'Subscription removed.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->guard($request);
        $query = CrmSubscriber::query()->latest('subscribed_at');
        $this->applyFilters($query, $request);
        $rows = $query->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Email', 'Source', 'Status', 'Subscribed at', 'Unsubscribed at']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->email, $row->source, $row->status, $row->subscribed_at, $row->unsubscribed_at]);
            }
            fclose($out);
        }, 'crm-subscriptions-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function applyFilters($query, Request $request): void
    {
        if ($search = trim((string) $request->query('subscriber_search'))) {
            $query->where('email', 'like', "%{$search}%");
        }
        if (in_array($request->query('subscriber_status'), ['active', 'unsubscribed'], true)) {
            $query->where('status', $request->query('subscriber_status'));
        }
        if ($request->filled('subscriber_source')) {
            $query->where('source', $request->query('subscriber_source'));
        }
    }

    private function guard(Request $request): void
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        abort_unless($user->isSuperAdmin(), 403);
    }
}
