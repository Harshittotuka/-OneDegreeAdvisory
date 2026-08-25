<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PageBuilderGuidance;
use App\Support\PageBuilderTokens;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "AI access" in the Page Builder: the setup guide plus the expiring tokens
 * that let a Claude or ChatGPT project author pages here.
 *
 * Super-admin only. Every other Page Builder screen is open to any CMS admin,
 * but a token is a long-lived credential that leaves the building — it belongs
 * with whoever owns the site, not with everyone who can edit a page.
 */
class PageBuilderTokenController extends Controller
{
    public function __construct(private PageBuilderTokens $tokens) {}

    public function index(): View|RedirectResponse
    {
        if (! $this->isSuperAdmin()) {
            return $this->denied();
        }

        return view('admin.brief.tokens', [
            'tokens' => $this->tokens->all(),
            'allowedDays' => PageBuilderTokens::ALLOWED_DAYS,
            'defaultDays' => PageBuilderTokens::DEFAULT_DAYS,
            'mcpUrl' => rtrim((string) config('app.url'), '/').'/mcp',
            'mcpEnabled' => (bool) config('page_api.mcp.enabled', true),
            'projectInstructions' => PageBuilderGuidance::projectInstructions(),
            // Shown once, immediately after generating. Never persisted.
            'freshToken' => session('page_builder_fresh_token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->isSuperAdmin()) {
            return $this->denied();
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'days' => ['required', 'integer', 'in:'.implode(',', PageBuilderTokens::ALLOWED_DAYS)],
        ]);

        $issued = $this->tokens->issue(
            $data['label'],
            (int) $data['days'],
            'cms-super-admin',
        );

        // Flashed, not persisted: this is the only time the plaintext exists
        // anywhere outside the admin's clipboard.
        return redirect()
            ->route('admin.pages.tokens.index')
            ->with('page_builder_fresh_token', [
                'token' => $issued['token'],
                'label' => $issued['model']->label,
                'expires_at' => $issued['model']->expires_at->toDayDateTimeString(),
                'days' => (int) $data['days'],
            ])
            ->with('status', 'Access token created — copy it now, it is not shown again.');
    }

    public function destroy(int $id): RedirectResponse
    {
        if (! $this->isSuperAdmin()) {
            return $this->denied();
        }

        $revoked = $this->tokens->revoke($id);

        return redirect()->route('admin.pages.tokens.index')->with(
            'status',
            $revoked ? 'Token revoked — it stops working immediately.' : 'That token was already revoked.',
        );
    }

    private function isSuperAdmin(): bool
    {
        return (bool) session('cms_super_admin');
    }

    private function denied(): RedirectResponse
    {
        return redirect()
            ->route('admin.pages.index')
            ->with('status', 'AI access tokens are managed by the super-admin.');
    }
}
