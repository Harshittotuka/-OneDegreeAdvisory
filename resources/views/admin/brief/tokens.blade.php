@extends('admin.layout')
@section('title', 'AI access — Page Builder')

@php
  // Pre-fill the paste block with the real token the moment one is generated,
  // so the admin copies something that already works instead of editing it.
  $instructions = $freshToken
    ? str_replace('PASTE_YOUR_TOKEN_HERE', $freshToken['token'], $projectInstructions)
    : $projectInstructions;
@endphp

@section('content')
  <div style="display:flex;flex-wrap:wrap;gap:14px;justify-content:space-between;align-items:flex-end;margin-bottom:18px;">
    <div>
      <h2 style="margin:0;font-size:1.3rem;">AI access</h2>
      <p class="hint" style="margin:4px 0 0;">Let Claude or ChatGPT build pages here. They can only create <strong>hidden drafts</strong> — publishing stays with you.</p>
    </div>
    <a class="btn btn-ghost" href="{{ route('admin.pages.index') }}"><i data-lucide="arrow-left"></i> Back to pages</a>
  </div>

  @if(! $mcpEnabled)
    <div class="panel" style="padding:14px 16px;margin-bottom:16px;border-left:3px solid #d9534f;">
      <strong style="color:#d9534f;">Turned off right now.</strong>
      <p class="hint" style="margin:4px 0 0;">Tokens made here will not work until <code>PAGE_MCP_ENABLED</code> is removed from <code>.env</code> (or set to true).</p>
    </div>
  @endif

  @if($freshToken)
    <div class="panel" style="padding:16px;margin-bottom:18px;border-left:3px solid #0f8a4f;">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
        <i data-lucide="key-round" style="width:18px;height:18px;color:#0f8a4f;"></i>
        <strong>Copy this now — you will not see it again.</strong>
      </div>
      <div style="display:flex;gap:8px;align-items:stretch;flex-wrap:wrap;">
        <input id="fresh-token" type="text" readonly value="{{ $freshToken['token'] }}"
               style="flex:1;min-width:320px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.86rem;background:#faf9ff;">
        <button class="btn btn-primary" type="button" data-copy="fresh-token"><i data-lucide="copy"></i> Copy</button>
      </div>
      <p class="hint" style="margin:8px 0 0;">
        “{{ $freshToken['label'] }}” · works for {{ $freshToken['days'] }} days · stops working {{ $freshToken['expires_at'] }}
      </p>
    </div>
  @endif

  {{-- ─────────────────────────  Setup guide  ───────────────────────── --}}
  <div class="panel" style="padding:20px 22px;margin-bottom:16px;">
    <h3 style="margin:0 0 4px;font-size:1.05rem;">How to set this up</h3>
    <p class="hint" style="margin:0 0 20px;">Three steps, about five minutes. Do them in order.</p>

    {{-- Step 1 --}}
    <div style="display:flex;gap:14px;margin-bottom:22px;">
      <div style="flex:none;width:26px;height:26px;border-radius:50%;background:var(--teal-soft);color:var(--teal);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.82rem;">1</div>
      <div style="flex:1;min-width:0;">
        <strong style="display:block;margin-bottom:6px;">Make a token</strong>
        <p class="hint" style="margin:0;">Use the form further down this page. A token is like a password that stops working on its own after the number of days you pick.</p>
      </div>
    </div>

    {{-- Step 2 --}}
    <div style="display:flex;gap:14px;margin-bottom:22px;">
      <div style="flex:none;width:26px;height:26px;border-radius:50%;background:var(--teal-soft);color:var(--teal);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.82rem;">2</div>
      <div style="flex:1;min-width:0;">
        <strong style="display:block;margin-bottom:6px;">Add this address to Claude or ChatGPT</strong>
        <div style="display:flex;gap:8px;margin:8px 0 12px;">
          <input id="mcp-url" type="text" readonly value="{{ $mcpUrl }}"
                 style="flex:1;min-width:0;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.84rem;background:#faf9ff;">
          <button class="btn btn-ghost btn-sm" type="button" data-copy="mcp-url"><i data-lucide="copy"></i> Copy</button>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;">
          <div style="background:#faf9ff;border:1px solid var(--line);border-radius:8px;padding:12px 14px;">
            <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--teal);margin-bottom:8px;">In Claude</div>
            <p class="hint" style="margin:0 0 6px;">Settings → Customize → Connectors → the <strong>+</strong> button → <strong>Add custom connector</strong>. Paste the address. Leave everything else blank.</p>
            <p class="hint" style="margin:0;"><strong>No such option?</strong> On a Team or Business plan only the account owner can add it. Ask them to do this one step.</p>
          </div>
          <div style="background:#faf9ff;border:1px solid var(--line);border-radius:8px;padding:12px 14px;">
            <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--teal);margin-bottom:8px;">In ChatGPT</div>
            <p class="hint" style="margin:0 0 6px;">Settings → Apps → Advanced settings → switch on <strong>Developer mode</strong>. Then <strong>Add custom connector</strong> and paste the address.</p>
            <p class="hint" style="margin:0;">Use the website, not the phone or desktop app. Leave Developer mode switched on or the connector disappears.</p>
          </div>
        </div>
      </div>
    </div>

    {{-- Step 3 --}}
    <div style="display:flex;gap:14px;">
      <div style="flex:none;width:26px;height:26px;border-radius:50%;background:var(--teal-soft);color:var(--teal);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.82rem;">3</div>
      <div style="flex:1;min-width:0;">
        <strong style="display:block;margin-bottom:6px;">Make a project and paste these instructions</strong>
        <p class="hint" style="margin:0 0 10px;">
          In Claude or ChatGPT, create a <strong>Project</strong> and open its <strong>Instructions</strong> box. Paste the text below.
          @if($freshToken)
            Your new token is already filled in.
          @else
            Replace <code>PASTE_YOUR_TOKEN_HERE</code> with your token.
          @endif
        </p>
        <textarea id="instructions" readonly rows="14"
                  style="width:100%;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.8rem;line-height:1.5;background:#faf9ff;resize:vertical;">{{ $instructions }}</textarea>
        <button class="btn btn-primary btn-sm" type="button" data-copy="instructions" style="margin-top:8px;"><i data-lucide="copy"></i> Copy instructions</button>
        <p class="hint" style="margin:10px 0 0;">
          These instructions matter. Without them the assistant may invent field names, or try to make separate HTML, CSS and JS files —
          and a page here is a <strong>single record</strong>, so anything custom has to go in one code block.
        </p>
      </div>
    </div>
  </div>

  {{-- ─────────────────────────  Try it  ───────────────────────── --}}
  <div class="panel" style="padding:18px 22px;margin-bottom:16px;">
    <h3 style="margin:0 0 4px;font-size:1.05rem;">Check it works</h3>
    <p class="hint" style="margin:0 0 14px;">Ask for a list first. If that works but making a page does not, the limit is on the assistant's side, not ours.</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;">
      <div style="background:#faf9ff;border:1px solid var(--line);border-radius:8px;padding:12px 14px;">
        <div class="hint" style="font-weight:700;margin-bottom:6px;">First, ask this</div>
        <div style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.82rem;">List the pages on the One Degree site.</div>
      </div>
      <div style="background:#faf9ff;border:1px solid var(--line);border-radius:8px;padding:12px 14px;">
        <div class="hint" style="font-weight:700;margin-bottom:6px;">Then try this</div>
        <div style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.82rem;">Create a page about studying in Ireland at /study-in-ireland, with a hero, two callouts side by side, and a costs table.</div>
      </div>
    </div>
    <p class="hint" style="margin:12px 0 0;">You get a preview link back. Open it while signed in here, then publish it from <a href="{{ route('admin.pages.index') }}">Page Builder</a>.</p>
  </div>

  {{-- ─────────────────────────  Limits  ───────────────────────── --}}
  <div class="panel" style="padding:18px 22px;margin-bottom:16px;">
    <h3 style="margin:0 0 14px;font-size:1.05rem;">What the assistant can and cannot do</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
      <div>
        <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#0f8a4f;margin-bottom:8px;">Can</div>
        <ul class="hint" style="margin:0;padding-left:18px;line-height:1.75;">
          <li>Make new pages — always hidden</li>
          <li>Change any hidden draft</li>
          <li>Copy a live page to edit the copy</li>
          <li>Write the web address, page title and meta description</li>
          <li>Use every block type, tags included</li>
        </ul>
      </div>
      <div>
        <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#d9534f;margin-bottom:8px;">Cannot</div>
        <ul class="hint" style="margin:0;padding-left:18px;line-height:1.75;">
          <li>Publish anything</li>
          <li>Change a page that is already live</li>
          <li>Delete a live page</li>
          <li>Add a payment section</li>
          <li>Touch anything else on the site</li>
        </ul>
      </div>
    </div>
  </div>

  {{-- ─────────────────────────  Generate  ───────────────────────── --}}
  <div class="panel" style="padding:18px 22px;margin-bottom:16px;">
    <h3 style="margin:0 0 4px;font-size:1.05rem;">Make a token</h3>
    <p class="hint" style="margin:0 0 14px;">Name it after where you will paste it, so you know which one to switch off later.</p>
    <form method="POST" action="{{ route('admin.pages.tokens.store') }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
      @csrf
      <div style="flex:1;min-width:240px;">
        <label for="tk-label" style="font-size:.74rem;">Name</label>
        <input id="tk-label" type="text" name="label" required maxlength="120"
               placeholder="e.g. My ChatGPT project" style="width:100%;">
      </div>
      <div style="min-width:150px;">
        <label for="tk-days" style="font-size:.74rem;">Works for</label>
        <select id="tk-days" name="days" style="width:100%;">
          @foreach($allowedDays as $days)
            <option value="{{ $days }}" @selected($days === $defaultDays)>{{ $days }} days</option>
          @endforeach
        </select>
      </div>
      <button class="btn btn-primary" type="submit"><i data-lucide="plus"></i> Make token</button>
    </form>
  </div>

  {{-- ─────────────────────────  Existing tokens  ───────────────────────── --}}
  <div class="panel" style="overflow:hidden;margin-bottom:16px;">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="background:#faf9ff;border-bottom:1px solid var(--line);">
          @foreach(['Token', 'Status', 'Stops working', 'Last used', 'Times used'] as $heading)
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
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;color:#0f8a4f;"><i data-lucide="check-circle-2" style="width:15px;height:15px;"></i> Working</span>
              @elseif($status === 'expired')
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;color:var(--muted);"><i data-lucide="clock" style="width:15px;height:15px;"></i> Ran out</span>
              @else
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;color:#d9534f;"><i data-lucide="ban" style="width:15px;height:15px;"></i> Switched off</span>
              @endif
            </td>
            <td style="padding:13px 16px;font-size:.84rem;">
              {{ $token->expires_at?->toFormattedDayDateString() ?? '—' }}
              @if($status === 'active')
                <div class="hint" style="margin:2px 0 0;">{{ $token->daysLeft() }} days left</div>
              @endif
            </td>
            <td style="padding:13px 16px;font-size:.84rem;">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
            <td style="padding:13px 16px;font-size:.84rem;">{{ $token->use_count }}</td>
            <td style="padding:13px 16px;text-align:right;white-space:nowrap;">
              @if($status !== 'revoked')
                <form method="POST" action="{{ route('admin.pages.tokens.destroy', $token->id) }}" style="display:inline;"
                      onsubmit="return confirm('Switch off “{{ $token->label }}”? Any project using it stops working straight away.');">
                  @csrf @method('DELETE')
                  <button class="btn btn-danger btn-sm" type="submit"><i data-lucide="ban"></i> Switch off</button>
                </form>
              @else
                <span class="hint">—</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="padding:26px 16px;text-align:center;" class="hint">
              No tokens yet. Make one above to connect Claude or ChatGPT.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ─────────────────────────  Troubleshooting  ───────────────────────── --}}
  <details class="panel" style="padding:16px 22px;">
    <summary style="cursor:pointer;font-weight:700;font-size:.95rem;">If something is not working</summary>
    <div style="overflow-x:auto;margin-top:14px;">
      <table style="width:100%;border-collapse:collapse;font-size:.9rem;">
        <tbody>
          @foreach([
            ['No “Add custom connector” option in Claude', 'On a Team or Business plan only the account owner can add one. Ask them to do step 2.'],
            ['The connector vanished from ChatGPT', 'Developer mode got switched off, or you are on the phone or desktop app. It only works on the website.'],
            ['“Couldn’t reach the MCP server”', 'Check the address is copied in full and ends in /mcp.'],
            ['“This site has no active Page Builder token”', 'Make one above. Or the one you used ran out, or was switched off.'],
            ['“That access token is missing, expired or revoked”', 'The token arrived but is not valid. Check for a stray space at either end.'],
            ['Listing pages works, but making one does not', 'Some plans allow reading but not writing. That is a limit on the assistant’s side, not ours.'],
            ['A page came out wrong', 'A mistyped field name is ignored rather than refused. Ask the assistant to read the page back and compare it with the block schema.'],
          ] as [$problem, $answer])
            <tr style="border-bottom:1px solid var(--line);">
              <td style="padding:11px 16px 11px 0;vertical-align:top;"><strong>{{ $problem }}</strong></td>
              <td style="padding:11px 0;vertical-align:top;color:var(--muted);">{{ $answer }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </details>

  <script>
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var el = document.getElementById(btn.dataset.copy);
        if (!el) return;
        el.select();
        var done = function () {
          var original = btn.innerHTML;
          btn.textContent = 'Copied';
          setTimeout(function () { btn.innerHTML = original; window.lucide && lucide.createIcons(); }, 1400);
        };
        if (navigator.clipboard) {
          navigator.clipboard.writeText(el.value).then(done, function () { document.execCommand('copy'); done(); });
        } else {
          document.execCommand('copy');
          done();
        }
      });
    });
  </script>
@endsection
