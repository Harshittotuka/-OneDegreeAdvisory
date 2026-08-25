@extends('admin.layout')
@section('title', 'Claude access — Page Builder')

@section('content')
  <div style="display:flex;flex-wrap:wrap;gap:14px;justify-content:space-between;align-items:flex-end;margin-bottom:18px;">
    <div>
      <h2 style="margin:0;font-size:1.3rem;">Claude access</h2>
      <p class="hint" style="margin:4px 0 0;">Expiring tokens that let a Claude project build pages here. Claude can only create <strong>hidden drafts</strong> — publishing stays with you.</p>
    </div>
    <a class="btn btn-ghost" href="{{ route('admin.pages.index') }}"><i data-lucide="arrow-left"></i> Back to pages</a>
  </div>

  @if(! $mcpEnabled)
    <div class="panel" style="padding:14px 16px;margin-bottom:16px;border-left:3px solid #d9534f;">
      <strong style="color:#d9534f;">The connector endpoint is switched off.</strong>
      <p class="hint" style="margin:4px 0 0;">Tokens issued here will not work until <code>PAGE_MCP_ENABLED</code> is removed from <code>.env</code> (or set to true).</p>
    </div>
  @endif

  @if($freshToken)
    <div class="panel" style="padding:16px;margin-bottom:18px;border-left:3px solid #0f8a4f;">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
        <i data-lucide="key-round" style="width:18px;height:18px;color:#0f8a4f;"></i>
        <strong>Copy this token now — it is never shown again.</strong>
      </div>
      <div style="display:flex;gap:8px;align-items:stretch;flex-wrap:wrap;">
        <input id="fresh-token" type="text" readonly value="{{ $freshToken['token'] }}"
               style="flex:1;min-width:320px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.86rem;background:#faf9ff;">
        <button class="btn btn-primary" type="button" onclick="copyFreshToken(this)"><i data-lucide="copy"></i> Copy</button>
      </div>
      <p class="hint" style="margin:8px 0 0;">
        “{{ $freshToken['label'] }}” · valid {{ $freshToken['days'] }} days · expires {{ $freshToken['expires_at'] }}
      </p>
    </div>
  @endif

  <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.15fr);gap:16px;align-items:start;">

    {{-- ── Generate ── --}}
    <div class="panel" style="padding:16px;">
      <h3 style="margin:0 0 4px;font-size:1rem;">Generate a token</h3>
      <p class="hint" style="margin:0 0 14px;">Name it after where you will paste it, so you know what to revoke later.</p>

      <form method="POST" action="{{ route('admin.pages.tokens.store') }}">
        @csrf
        <div style="margin-bottom:12px;">
          <label for="tk-label" style="font-size:.74rem;">Label</label>
          <input id="tk-label" type="text" name="label" required maxlength="120"
                 placeholder="e.g. Claude project — website pages" style="width:100%;">
        </div>
        <div style="margin-bottom:16px;">
          <label for="tk-days" style="font-size:.74rem;">Valid for</label>
          <select id="tk-days" name="days" style="width:100%;">
            @foreach($allowedDays as $days)
              <option value="{{ $days }}" @selected($days === $defaultDays)>{{ $days }} days</option>
            @endforeach
          </select>
        </div>
        <button class="btn btn-primary" type="submit"><i data-lucide="plus"></i> Generate token</button>
      </form>
    </div>

    {{-- ── Connect ── --}}
    <div class="panel" style="padding:16px;">
      <h3 style="margin:0 0 4px;font-size:1rem;">Connect it to Claude</h3>
      <p class="hint" style="margin:0 0 14px;">In claude.ai: <strong>Settings → Connectors → Add custom connector</strong>.</p>

      <label style="font-size:.74rem;">Connector URL</label>
      <div style="display:flex;gap:8px;margin-bottom:14px;">
        <input id="mcp-url" type="text" readonly value="{{ $mcpUrl }}"
               style="flex:1;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.84rem;background:#faf9ff;">
        <button class="btn btn-ghost btn-sm" type="button" onclick="copyValue('mcp-url', this)"><i data-lucide="copy"></i></button>
      </div>

      <p class="hint" style="margin:0 0 8px;"><strong>Then give Claude the token, whichever of these your account offers:</strong></p>
      <ol class="hint" style="margin:0;padding-left:18px;line-height:1.65;">
        <li>
          <strong>Request headers</strong> in the same dialog — header <code>Authorization</code>,
          value <code>Bearer &lt;token&gt;</code> (include the word Bearer and the space).
          This is the tidier option, but it is a gradual beta so the field may not appear yet.
        </li>
        <li>
          <strong>No headers field?</strong> Add the connector with the URL alone, then paste the
          token into your Claude <em>project instructions</em>:
          <code style="display:block;margin:6px 0;padding:8px;background:#faf9ff;border-radius:6px;white-space:pre-wrap;">Page Builder token: &lt;token&gt;
Pass it as the `token` argument on every Page Builder tool call.</code>
        </li>
      </ol>

      <p class="hint" style="margin:12px 0 0;padding-top:12px;border-top:1px solid var(--line);">
        Then just ask: <em>“Create a page about studying in Ireland at /study-in-ireland, with a hero, a costs table and an FAQ.”</em>
        Claude builds it hidden and hands you a preview link.
      </p>
    </div>
  </div>

  {{-- ── Existing tokens ── --}}
  <div class="panel" style="overflow:hidden;margin-top:16px;">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="background:#faf9ff;border-bottom:1px solid var(--line);">
          @foreach(['Token', 'Status', 'Expires', 'Last used', 'Uses'] as $heading)
            <th style="text-align:left;padding:12px 16px;font-size:.74rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;">{{ $heading }}</th>
          @endforeach
          <th style="text-align:right;padding:12px 16px;font-size:.74rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($tokens as $token)
          @php($status = $token->status())
          <tr style="border-bottom:1px solid var(--line);">
            <td style="padding:13px 16px;">
              <strong>{{ $token->label }}</strong>
              <div class="hint" style="margin:2px 0 0;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">odp_pb_{{ $token->hint }}…</div>
            </td>
            <td style="padding:13px 16px;">
              @if($status === 'active')
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;color:#0f8a4f;"><i data-lucide="check-circle-2" style="width:15px;height:15px;"></i> Active</span>
              @elseif($status === 'expired')
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;color:var(--muted);"><i data-lucide="clock" style="width:15px;height:15px;"></i> Expired</span>
              @else
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;color:#d9534f;"><i data-lucide="ban" style="width:15px;height:15px;"></i> Revoked</span>
              @endif
            </td>
            <td style="padding:13px 16px;font-size:.84rem;">
              {{ $token->expires_at?->toFormattedDayDateString() ?? '—' }}
              @if($status === 'active')
                <div class="hint" style="margin:2px 0 0;">{{ $token->daysLeft() }} days left</div>
              @endif
            </td>
            <td style="padding:13px 16px;font-size:.84rem;">
              {{ $token->last_used_at?->diffForHumans() ?? 'Never' }}
            </td>
            <td style="padding:13px 16px;font-size:.84rem;">{{ $token->use_count }}</td>
            <td style="padding:13px 16px;text-align:right;white-space:nowrap;">
              @if($status !== 'revoked')
                <form method="POST" action="{{ route('admin.pages.tokens.destroy', $token->id) }}" style="display:inline;"
                      onsubmit="return confirm('Revoke “{{ $token->label }}”? Any Claude project using it stops working immediately.');">
                  @csrf @method('DELETE')
                  <button class="btn btn-danger btn-sm" type="submit"><i data-lucide="ban"></i> Revoke</button>
                </form>
              @else
                <span class="hint">—</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="padding:26px 16px;text-align:center;" class="hint">
              No tokens yet. Generate one above to connect a Claude project.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <script>
    function copyValue(id, btn) {
      const el = document.getElementById(id);
      if (!el) return;
      el.select();
      navigator.clipboard.writeText(el.value).then(() => {
        const original = btn.innerHTML;
        btn.textContent = 'Copied';
        setTimeout(() => { btn.innerHTML = original; window.lucide?.createIcons(); }, 1400);
      });
    }
    function copyFreshToken(btn) { copyValue('fresh-token', btn); }
  </script>
@endsection
