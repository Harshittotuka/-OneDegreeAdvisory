@php $shortlistErrors = $errors->getBag('shortlist'); @endphp
<section class="workspace pdf-shortlist">
    <style>
        .pdf-shortlist { --pl-accent:#4f46e5; --pl-line:#d9d9e6; }
        .pdf-shortlist .pl-hero { position:relative; overflow:hidden; padding:24px 26px; margin-bottom:18px;
            background:linear-gradient(118deg,#fff 45%,#f0efff 100%); border:1px solid var(--pl-line); border-radius:16px; }
        .pdf-shortlist .pl-kicker { display:inline-flex; align-items:center; gap:7px; color:var(--pl-accent);
            font-size:.72rem; font-weight:800; letter-spacing:.09em; text-transform:uppercase; }
        .pdf-shortlist .pl-kicker svg { width:15px; height:15px; }
        .pdf-shortlist .pl-hero h2 { margin:9px 0 6px; font-size:clamp(1.35rem,2.6vw,1.9rem); letter-spacing:-.03em; color:#1a1a2e; }
        .pdf-shortlist .pl-hero p { max-width:64ch; margin:0; color:#5c5c6e; line-height:1.6; font-size:.92rem; }
        .pdf-shortlist .pl-panel { padding:24px; background:#fff; border:1px solid var(--pl-line); border-radius:16px; }
        .pdf-shortlist .pl-flow { display:grid; grid-template-columns:1fr auto 1fr; gap:16px; align-items:stretch; }
        .pdf-shortlist .pl-arrow { display:grid; place-items:center; color:#9b9cab; font-size:1.5rem; font-weight:700; }
        .pdf-shortlist .pl-card { position:relative; min-height:210px; border:2px dashed var(--pl-line); border-radius:14px;
            background:#fbfbfd; transition:border-color .15s, background .15s, transform .15s; }
        .pdf-shortlist .pl-card:hover, .pdf-shortlist .pl-card.is-over { border-color:var(--pl-accent); background:#f6f5ff; transform:translateY(-2px); }
        .pdf-shortlist .pl-card.has-file { border-style:solid; border-color:#28c76f; background:#f5fcf8; }
        .pdf-shortlist .pl-card input { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; z-index:2; }
        .pdf-shortlist .pl-content { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center;
            justify-content:center; padding:22px; text-align:center; pointer-events:none; }
        .pdf-shortlist .pl-icon { display:grid; place-items:center; width:54px; height:54px; border-radius:16px; margin-bottom:12px; }
        .pdf-shortlist .pl-icon svg { width:25px; height:25px; }
        .pdf-shortlist .pl-card h3 { margin:0 0 5px; font-size:.98rem; color:#1a1a2e; }
        .pdf-shortlist .pl-card p { margin:0; color:#5c5c6e; font-size:.8rem; line-height:1.5; }
        .pdf-shortlist .pl-name { display:none; max-width:100%; margin-top:10px; padding:5px 10px; border-radius:999px;
            background:#e2f7ea; color:#187345; font-size:.74rem; font-weight:800; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .pdf-shortlist .pl-card.has-file .pl-name { display:block; }
        .pdf-shortlist .pl-note { display:flex; align-items:flex-start; gap:10px; margin:18px 0; padding:13px 15px;
            border-radius:11px; background:#f7f4eb; color:#655f50; font-size:.82rem; line-height:1.55; }
        .pdf-shortlist .pl-note svg { flex:0 0 auto; width:17px; height:17px; margin-top:1px; color:#a06b00; }
        .pdf-shortlist .pl-actions { display:flex; align-items:center; justify-content:space-between; gap:16px;
            padding-top:18px; border-top:1px solid var(--pl-line); }
        .pdf-shortlist .pl-actions .pl-hint { margin:0; color:#7a7a8c; font-size:.78rem; }
        .pdf-shortlist .pl-submit { min-width:220px; justify-content:center; }
        .pdf-shortlist .pl-submit[disabled] { opacity:.55; cursor:not-allowed; }
        .pdf-shortlist .pl-errors { margin-bottom:16px; padding:13px 15px; border:1px solid #f0b6b6; border-radius:11px;
            background:#fff4f4; color:#a93232; font-size:.85rem; }
        .pdf-shortlist .pl-errors ul { margin:5px 0 0 18px; padding:0; }
        @media (max-width:760px) {
            .pdf-shortlist .pl-flow { grid-template-columns:1fr; }
            .pdf-shortlist .pl-arrow { transform:rotate(90deg); min-height:18px; }
            .pdf-shortlist .pl-actions { flex-direction:column; align-items:stretch; }
            .pdf-shortlist .pl-submit { width:100%; }
        }
    </style>

    <div class="pl-hero">
        <span class="pl-kicker"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2.5H7a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7.5Z"/><path d="M14 2.5v5h5"/></svg> Report production tool</span>
        <h2>Replace the report's last page with an Excel shortlist</h2>
        <p>Upload the completed career-report PDF and the university-shortlisting workbook. Every earlier PDF page stays unchanged; only the final page is replaced with a branded, landscape comparison table.</p>
    </div>

    @if($shortlistErrors->any())
        <div class="pl-errors" role="alert">
            <b>The PDF was not generated.</b>
            <ul>
                @foreach($shortlistErrors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form class="pl-panel" method="POST" action="{{ route('crm.pdf-shortlisting.generate') }}" enctype="multipart/form-data" id="shortlist-form">
        @csrf
        <div class="pl-flow">
            <label class="pl-card" data-upload-card>
                <input type="file" name="report_pdf" accept="application/pdf,.pdf" required data-file-input>
                <span class="pl-content">
                    <span class="pl-icon" style="background:#fce9e7;color:#d34232;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2.5H7a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7.5Z"/><path d="M14 2.5v5h5"/><path d="M8.5 13h7M8.5 16.5h7"/></svg></span>
                    <h3>Career report PDF</h3>
                    <p>Choose or drop one PDF<br>Maximum size: 25 MB</p>
                    <span class="pl-name" data-file-name></span>
                </span>
            </label>

            <span class="pl-arrow" aria-hidden="true">+</span>

            <label class="pl-card" data-upload-card>
                <input type="file" name="shortlist_excel" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.xlsx" required data-file-input>
                <span class="pl-content">
                    <span class="pl-icon" style="background:#e5f7ed;color:#238c56;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/></svg></span>
                    <h3>University shortlist Excel</h3>
                    <p>Choose or drop one .xlsx file<br>Maximum size: 5 MB</p>
                    <span class="pl-name" data-file-name></span>
                </span>
            </label>
        </div>

        <div class="pl-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>The first data sheet is used. Put attribute names in column A and one university option in each remaining column, matching the supplied sample workbook. The sheet name (for example, <b>USA</b>) becomes the country in the page title.</span>
        </div>

        <div class="pl-actions">
            <p class="pl-hint">Uploaded files are processed temporarily and are not retained.</p>
            <button class="btn btn-primary pl-submit" type="submit" id="shortlist-submit" disabled>
                <span data-submit-label>Generate merged PDF</span>
            </button>
        </div>
    </form>
</section>

<script>
    (function () {
        var form = document.getElementById('shortlist-form');
        var submit = document.getElementById('shortlist-submit');
        if (!form || !submit) return;

        // This form returns a file download, so it must submit natively. Stop the
        // CRM's global AJAX submit handler (on document) from intercepting it.
        form.addEventListener('submit', function (event) { event.stopPropagation(); });

        var inputs = Array.prototype.slice.call(form.querySelectorAll('[data-file-input]'));
        var label = submit.querySelector('[data-submit-label]');

        function refresh() {
            var ready = inputs.every(function (input) { return input.files && input.files.length === 1; });
            submit.disabled = !ready;
            inputs.forEach(function (input) {
                var card = input.closest('[data-upload-card]');
                var nameEl = card.querySelector('[data-file-name]');
                var selected = input.files && input.files[0];
                card.classList.toggle('has-file', !!selected);
                nameEl.textContent = selected ? selected.name : '';
            });
        }

        inputs.forEach(function (input) {
            var card = input.closest('[data-upload-card]');
            input.addEventListener('change', refresh);
            ['dragenter', 'dragover'].forEach(function (name) {
                card.addEventListener(name, function () { card.classList.add('is-over'); });
            });
            ['dragleave', 'drop'].forEach(function (name) {
                card.addEventListener(name, function () { card.classList.remove('is-over'); });
            });
        });

        form.addEventListener('submit', function () {
            submit.disabled = true;
            if (label) label.textContent = 'Generating PDF…';
            // The download keeps this page in place, so restore the button
            // shortly after so another report can be produced.
            setTimeout(function () {
                if (label) label.textContent = 'Generate merged PDF';
                refresh();
            }, 4000);
        });

        refresh();
    })();
</script>
