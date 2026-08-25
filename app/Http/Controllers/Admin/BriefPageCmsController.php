<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PaymentSectionOtpMail;
use App\Support\BriefPageStore;
use App\Support\BriefPresets;
use App\Support\BriefSchema;
use App\Support\PersistsInlineImages;
use App\Support\SanitizesBriefLayout;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * The Brief Page Builder — a super-admin CMS for creating .odp-* "brief" pages
 * (Europe, intelligence briefs, etc.) from composable blocks. Mirrors the About
 * CMS pattern (schema-driven sanitize + PersistsInlineImages), extended to manage
 * many pages and to drive a block-builder UI with a live preview.
 */
class BriefPageCmsController extends Controller
{
    use PersistsInlineImages, SanitizesBriefLayout;

    public function __construct(private BriefPageStore $store) {}

    /* ───────────────────────── Pages list ───────────────────────── */

    public function index(): View
    {
        $this->guard();

        return view('admin.brief.index', [
            'pages' => $this->store->all(),
        ]);
    }

    public function storePage(Request $request): RedirectResponse
    {
        $this->guard();

        $request->validate(['title' => ['required', 'string', 'max:160']]);

        $slug = $this->store->uniqueSlug((string) $request->input('title'));
        $page = [
            'slug' => $slug,
            'path' => '/briefs/'.$slug,
            'title' => trim((string) $request->input('title')),
            'page_title' => '',
            'meta_description' => '',
            'visible' => false,
            'sections' => [
                ['id' => 'hero', 'type' => 'hero', 'visible' => true, 'data' => BriefSchema::blank('hero')],
            ],
        ];

        $this->store->save($page);

        return redirect()->route('admin.pages.edit', $slug)->with('status', 'Page created — start building.');
    }

    /* ───────────────────────── Builder ───────────────────────── */

    /** Old two-pane editor is superseded by the full-screen studio. */
    public function edit(string $slug): RedirectResponse
    {
        return redirect()->route('admin.pages.studio', $slug);
    }

    /** Full-screen drag-and-drop visual builder. */
    public function studio(string $slug): View|RedirectResponse
    {
        $this->guard();

        $page = $this->store->find($slug);
        if ($page === null) {
            return redirect()->route('admin.pages.index')->with('status', 'That page no longer exists.');
        }

        // Render each component preset once so the palette can show a live thumbnail.
        $presets = [];
        foreach (BriefPresets::all() as $key => $p) {
            $presets[$key] = $p + [
                'node' => view('admin.brief._blocknode', ['type' => $p['type'], 'data' => $p['data']])->render(),
            ];
        }

        return view('admin.brief.studio', [
            'page' => $page,
            'types' => BriefSchema::types(),
            'presets' => $presets,
        ]);
    }

    /** A ready-made component (block with data): rendered node + settings form. */
    public function preset(Request $request): JsonResponse
    {
        $this->guard();

        $preset = BriefPresets::find((string) $request->query('key', ''));
        if ($preset === null) {
            abort(404);
        }

        $block = ['id' => 'b'.Str::random(7), 'type' => $preset['type'], 'visible' => true, 'data' => $preset['data']];

        return response()->json([
            'id' => $block['id'],
            'type' => $block['type'],
            'node' => view('admin.brief._blocknode', ['type' => $block['type'], 'data' => $block['data']])->render(),
            'form' => view('admin.brief._settings', ['block' => $block, 'def' => BriefSchema::type($block['type'])])->render(),
        ]);
    }

    /** Save the whole page (meta + grid layout) from the studio. */
    public function save(Request $request, string $slug): JsonResponse
    {
        $this->guard();

        $page = $this->store->find($slug);
        if ($page === null) {
            return response()->json(['ok' => false, 'message' => 'Page not found.'], 404);
        }

        $layout = $request->input('layout');
        if (! is_array($layout)) {
            return response()->json(['ok' => false, 'message' => 'Bad payload.'], 422);
        }

        // Security gate: a page containing a payment section can only be saved
        // with a valid authorization OTP — proves the editor is allowed to
        // publish a live payment gateway (see requestPaymentOtp/verifyPaymentOtp).
        if ($this->layoutHasPayment($layout) && ! $this->paymentAuthValid()) {
            return response()->json([
                'ok' => false,
                'need_payment_otp' => true,
                'message' => 'Saving a payment section needs an authorization code.',
            ], 403);
        }

        $page['title'] = mb_substr(trim((string) $request->input('title', $page['title'] ?? 'Untitled')), 0, 160) ?: 'Untitled';
        $page['visible'] = $request->boolean('visible');
        $page['path'] = $this->cleanPath((string) $request->input('path', $page['path'] ?? ''), $page, $this->store);
        // Inline (freshly cropped/uploaded) images → disk, then sanitize every block.
        $layout = $this->persistInlineImages($layout, 'brief');
        $page['layout'] = $this->sanitizeLayout($layout);
        $page['page_title'] = Seo::title((string) $request->input('page_title', $page['page_title'] ?? ''), '', 90);
        $page['meta_description'] = Seo::description((string) $request->input('meta_description', $page['meta_description'] ?? ''), '', 170);
        unset($page['sections']); // fully migrated to the grid layout

        $this->store->save($page, $slug);

        return response()->json(['ok' => true, 'message' => 'Page saved.', 'path' => $page['path']]);
    }

    /** Blank block for the palette: returns its rendered node + settings form. */
    public function block(Request $request): JsonResponse
    {
        $this->guard();

        $type = (string) $request->query('type', '');
        if (! BriefSchema::isType($type)) {
            abort(404);
        }
        $block = ['id' => 'b'.Str::random(7), 'type' => $type, 'visible' => true, 'data' => BriefSchema::blank($type)];

        return response()->json([
            'id' => $block['id'],
            'type' => $type,
            'node' => view('admin.brief._blocknode', ['type' => $type, 'data' => $block['data']])->render(),
            'form' => view('admin.brief._settings', ['block' => $block, 'def' => BriefSchema::type($type)])->render(),
        ]);
    }

    /** Re-render one block to HTML after an edit in the drawer. */
    public function render(Request $request): JsonResponse
    {
        $this->guard();

        $type = (string) $request->input('type', '');
        if (! BriefSchema::isType($type)) {
            return response()->json(['node' => ''], 422);
        }
        $data = $this->sanitizeData($type, is_array($request->input('data')) ? $request->input('data') : []);

        return response()->json([
            'node' => view('admin.brief._blocknode', ['type' => $type, 'data' => $data])->render(),
        ]);
    }

    /* ──────────────── Payment-section authorization OTP ──────────────── */

    /** Email a one-time code to the configured approver(s) before a payment section can be saved. */
    public function requestPaymentOtp(Request $request): JsonResponse
    {
        $this->guard();

        $cfg = (array) config('site.payment_section_otp');
        $recipients = array_values(array_filter((array) ($cfg['recipients'] ?? [])));
        if ($recipients === []) {
            return response()->json(['ok' => false, 'message' => 'No authorization email is configured. Set PAYMENT_SECTION_OTP_EMAILS.'], 503);
        }

        $page = $request->filled('slug') ? $this->store->find((string) $request->input('slug')) : null;
        $pageTitle = (string) ($page['title'] ?? trim((string) $request->input('title')) ?: 'New page');
        $pagePath = (string) ($page['path'] ?? '—');

        $ttl = max(3, min(30, (int) ($cfg['ttl_minutes'] ?? 10)));
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        session()->put('cms_payment_otp', [
            'hash' => $this->paymentOtpHash($otp),
            'expires' => now()->addMinutes($ttl)->timestamp,
            'attempts' => 0,
        ]);

        try {
            $mailer = ($cfg['mailer'] ?? null) ?: config('mail.default');
            Mail::mailer($mailer)->to($recipients)->send(new PaymentSectionOtpMail($otp, $ttl, $pageTitle, $pagePath));
        } catch (Throwable $e) {
            report($e);
            session()->forget('cms_payment_otp');

            return response()->json(['ok' => false, 'message' => 'The authorization email could not be sent. Try again shortly.'], 503);
        }

        return response()->json([
            'ok' => true,
            'expires_in' => $ttl * 60,
            'message' => 'Authorization code sent to the payment approver.',
        ]);
    }

    /** Verify the code; on success, authorize payment-section saves for a short window. */
    public function verifyPaymentOtp(Request $request): JsonResponse
    {
        $this->guard();

        $validated = $request->validate(['otp' => ['required', 'digits:6']]);

        $cfg = (array) config('site.payment_section_otp');
        $state = session('cms_payment_otp');
        if (! is_array($state)) {
            return response()->json(['ok' => false, 'message' => 'Request a new authorization code.'], 422);
        }
        if ((int) ($state['expires'] ?? 0) < now()->timestamp) {
            session()->forget('cms_payment_otp');

            return response()->json(['ok' => false, 'message' => 'The authorization code has expired. Request a new one.'], 422);
        }
        $maxAttempts = max(3, min(10, (int) ($cfg['max_attempts'] ?? 5)));
        if ((int) ($state['attempts'] ?? 0) >= $maxAttempts) {
            session()->forget('cms_payment_otp');

            return response()->json(['ok' => false, 'message' => 'Too many incorrect codes. Request a new one.'], 429);
        }

        $state['attempts'] = (int) ($state['attempts'] ?? 0) + 1;
        session()->put('cms_payment_otp', $state);

        if (! hash_equals((string) ($state['hash'] ?? ''), $this->paymentOtpHash($validated['otp']))) {
            return response()->json(['ok' => false, 'message' => 'The authorization code is incorrect.'], 422);
        }

        $window = max(1, min(120, (int) ($cfg['window_minutes'] ?? 10)));
        session()->forget('cms_payment_otp');
        session()->put('cms_payment_otp_ok_until', now()->addMinutes($window)->timestamp);

        return response()->json(['ok' => true, 'message' => 'Authorized — you can save the payment section now.']);
    }

    /** True while a recent OTP verification still authorizes payment-section saves. */
    private function paymentAuthValid(): bool
    {
        $until = (int) session('cms_payment_otp_ok_until', 0);

        return $until > 0 && $until >= now()->timestamp;
    }

    private function paymentOtpHash(string $otp): string
    {
        return hash_hmac('sha256', $otp, (string) config('app.key').'|payment-section-otp');
    }

    /* ───────────────────────── Page actions ───────────────────────── */

    public function duplicate(string $slug): RedirectResponse
    {
        $this->guard();
        $copy = $this->store->duplicate($slug);

        return $copy
            ? redirect()->route('admin.pages.edit', $copy['slug'])->with('status', 'Page duplicated.')
            : redirect()->route('admin.pages.index')->with('status', 'Could not duplicate that page.');
    }

    public function toggleVisibility(string $slug): RedirectResponse
    {
        $this->guard();
        $page = $this->store->find($slug);
        if ($page !== null) {
            $this->store->setVisibility($slug, ! ($page['visible'] ?? true));
        }

        return redirect()->route('admin.pages.index')->with('status', 'Visibility updated.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        $this->guard();
        $this->store->delete($slug);

        return redirect()->route('admin.pages.index')->with('status', 'Page deleted.');
    }

    /* ───────────────────────── Images ───────────────────────── */

    public function upload(Request $request): JsonResponse
    {
        $this->guard();
        $request->validate(['file' => ['required', 'image', 'max:8192']]);
        $path = $request->file('file')->store('brief', 'public');

        return response()->json(['url' => asset('storage/'.$path)]);
    }

    public function importUrl(Request $request): JsonResponse
    {
        $this->guard();

        $url = trim((string) $request->input('url', ''));
        if (! preg_match('#^https?://#i', $url)) {
            return response()->json(['ok' => false, 'message' => 'Enter a valid http(s) image URL.'], 422);
        }

        try {
            $resp = Http::timeout(12)->withHeaders(['User-Agent' => 'OneDegreeCMS/1.0'])->get($url);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Could not fetch that image.'], 422);
        }

        $type = strtolower((string) $resp->header('Content-Type'));
        if (! $resp->ok() || ! str_starts_with($type, 'image/')) {
            return response()->json(['ok' => false, 'message' => 'That URL is not a reachable image.'], 422);
        }

        $body = $resp->body();
        if (strlen($body) > 8 * 1024 * 1024) {
            return response()->json(['ok' => false, 'message' => 'Image is larger than 8 MB.'], 422);
        }

        return response()->json(['ok' => true, 'url' => 'data:'.$type.';base64,'.base64_encode($body)]);
    }

    /* ───────────────────────── Sanitization ───────────────────────── */

    /**
     * Page Builder is open to every signed-in CMS admin — the route group already
     * enforces cms.auth. Kept as a per-action call so access can be tightened
     * again in one place later if needed.
     */
    private function guard(): void
    {
        // Intentionally open to standard and super admins.
    }
}
