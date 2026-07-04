<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\NewsletterStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    public function __construct(private NewsletterStore $store)
    {
    }

    public function index(): View
    {
        return view('admin.newsletter.index', [
            'portal' => 'admin',
            'subscribers' => $this->store->all(),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $email = trim((string) $request->input('email', ''));
        if ($email !== '') {
            $this->store->delete($email);
        }

        return back()->with('status', 'Subscriber removed.');
    }

    /** Download every subscriber as a CSV. */
    public function export(): StreamedResponse
    {
        $rows = $this->store->all();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Email', 'Source', 'Subscribed at']);
            foreach ($rows as $r) {
                fputcsv($out, [$r['email'] ?? '', $r['source'] ?? '', $r['date'] ?? '']);
            }
            fclose($out);
        }, 'newsletter-subscribers-'.date('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
