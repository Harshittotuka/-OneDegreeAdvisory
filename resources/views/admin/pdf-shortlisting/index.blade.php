@extends('admin.layout')
@section('title', 'PDF Shortlisting Merger')

@push('head')
<style>
  .merge-shell { max-width: 1040px; margin: 0 auto; }
  .merge-hero { position: relative; overflow: hidden; padding: 28px 30px; margin-bottom: 22px;
    background: linear-gradient(118deg, #fff 45%, #f0efff 100%); }
  .merge-hero::after { content: ''; position: absolute; width: 220px; height: 220px; right: -72px; top: -96px;
    border: 36px solid rgba(102,108,255,.10); border-radius: 50%; }
  .merge-kicker { display: inline-flex; align-items: center; gap: 7px; color: var(--teal); font-size: .76rem;
    font-weight: 800; letter-spacing: .09em; text-transform: uppercase; }
  .merge-kicker i { width: 15px; height: 15px; }
  .merge-hero h2 { margin: 9px 0 7px; font-size: clamp(1.55rem, 3vw, 2.15rem); letter-spacing: -.035em; }
  .merge-hero p { max-width: 66ch; margin: 0; color: var(--muted); line-height: 1.65; }

  .merge-panel { padding: 26px; }
  .merge-flow { display: grid; grid-template-columns: 1fr auto 1fr; gap: 16px; align-items: stretch; }
  .flow-arrow { display: grid; place-items: center; color: #9b9cab; }
  .flow-arrow i { width: 24px; height: 24px; }

  .upload-card { position: relative; min-height: 224px; border: 2px dashed #d5d5e3; border-radius: 15px;
    background: #fbfbfd; transition: border-color .15s, background .15s, transform .15s; }
  .upload-card:hover, .upload-card.is-over { border-color: var(--teal); background: #f6f5ff; transform: translateY(-2px); }
  .upload-card.has-file { border-style: solid; border-color: #28c76f; background: #f5fcf8; }
  .upload-card input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2; }
  .upload-content { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 24px; text-align: center; pointer-events: none; }
  .upload-icon { display: grid; place-items: center; width: 58px; height: 58px; border-radius: 17px;
    background: var(--icon-bg); color: var(--icon-color); margin-bottom: 13px; }
  .upload-icon i { width: 27px; height: 27px; }
  .upload-card h3 { margin: 0 0 5px; font-size: 1rem; }
  .upload-card p { margin: 0; color: var(--muted); font-size: .82rem; line-height: 1.5; }
  .file-name { display: none; max-width: 100%; margin-top: 11px; padding: 5px 10px; border-radius: 999px;
    background: #e2f7ea; color: #187345; font-size: .76rem; font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .upload-card.has-file .file-name { display: block; }

  .merge-note { display: flex; align-items: flex-start; gap: 11px; margin: 20px 0; padding: 14px 16px;
    border-radius: 11px; background: #f7f4eb; color: #655f50; font-size: .84rem; line-height: 1.55; }
  .merge-note i { flex: 0 0 auto; width: 18px; height: 18px; margin-top: 1px; color: #a06b00; }
  .merge-actions { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding-top: 20px;
    border-top: 1px solid var(--line); }
  .merge-actions p { margin: 0; color: var(--muted); font-size: .8rem; }
  .merge-submit { min-width: 235px; justify-content: center; }
  .merge-submit[disabled] { opacity: .55; cursor: not-allowed; transform: none; }
  .merge-submit .spin { display: none; animation: mergeSpin .8s linear infinite; }
  .merge-submit.is-loading .normal { display: none; }
  .merge-submit.is-loading .spin { display: inline-block; }
  @keyframes mergeSpin { to { transform: rotate(360deg); } }

  .merge-errors { margin-bottom: 18px; padding: 14px 16px; border: 1px solid #f0b6b6; border-radius: 11px;
    background: #fff4f4; color: #a93232; font-size: .86rem; }
  .merge-errors ul { margin: 5px 0 0 20px; padding: 0; }

  @media (max-width: 760px) {
    .merge-flow { grid-template-columns: 1fr; }
    .flow-arrow { transform: rotate(90deg); min-height: 20px; }
    .merge-actions { align-items: stretch; flex-direction: column; }
    .merge-submit { width: 100%; }
  }
</style>
@endpush

@section('content')
<div class="merge-shell">
  <section class="panel merge-hero">
    <span class="merge-kicker"><i data-lucide="files"></i> Report production tool</span>
    <h2>Replace the report's last page with an Excel shortlist</h2>
    <p>Upload the completed career-report PDF and the university-shortlisting workbook. The tool keeps every earlier PDF page unchanged and replaces only the final page with a branded, landscape comparison table.</p>
  </section>

  @if($errors->any())
    <div class="merge-errors" role="alert">
      <b>The PDF was not generated.</b>
      <ul>
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form class="panel merge-panel" method="POST" action="{{ route('admin.pdf-shortlisting.generate') }}" enctype="multipart/form-data" id="merge-form">
    @csrf

    <div class="merge-flow">
      <label class="upload-card" data-upload-card style="--icon-bg:#fce9e7;--icon-color:#d34232;">
        <input type="file" name="report_pdf" accept="application/pdf,.pdf" required data-file-input>
        <span class="upload-content">
          <span class="upload-icon"><i data-lucide="file-text"></i></span>
          <h3>Career report PDF</h3>
          <p>Choose or drop one PDF<br>Maximum size: 25 MB</p>
          <span class="file-name" data-file-name></span>
        </span>
      </label>

      <span class="flow-arrow" aria-hidden="true"><i data-lucide="plus"></i></span>

      <label class="upload-card" data-upload-card style="--icon-bg:#e5f7ed;--icon-color:#238c56;">
        <input type="file" name="shortlist_excel" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.xlsx" required data-file-input>
        <span class="upload-content">
          <span class="upload-icon"><i data-lucide="sheet"></i></span>
          <h3>University shortlist Excel</h3>
          <p>Choose or drop one .xlsx file<br>Maximum size: 5 MB</p>
          <span class="file-name" data-file-name></span>
        </span>
      </label>
    </div>

    <div class="merge-note">
      <i data-lucide="info"></i>
      <span>The first data sheet is used. Put attribute names in column A and one university option in each remaining column, matching the supplied sample workbook. The sheet name (for example, <b>USA</b>) becomes the country in the page title.</span>
    </div>

    <div class="merge-actions">
      <p><i data-lucide="shield-check" style="width:14px;height:14px;vertical-align:-2px;"></i> Uploaded files are processed temporarily and are not retained.</p>
      <button class="btn btn-primary merge-submit" type="submit" id="merge-submit" disabled>
        <i class="normal" data-lucide="download" style="width:17px;height:17px;"></i>
        <i class="spin" data-lucide="loader-circle" style="width:17px;height:17px;"></i>
        <span data-submit-label>Generate merged PDF</span>
      </button>
    </div>
  </form>
</div>

<script>
  (function () {
    var form = document.getElementById('merge-form');
    var submit = document.getElementById('merge-submit');
    if (!form || !submit) return;

    var inputs = Array.prototype.slice.call(form.querySelectorAll('[data-file-input]'));
    function refresh() {
      var ready = inputs.every(function (input) { return input.files && input.files.length === 1; });
      submit.disabled = !ready;
      inputs.forEach(function (input) {
        var card = input.closest('[data-upload-card]');
        var name = card.querySelector('[data-file-name]');
        var selected = input.files && input.files[0];
        card.classList.toggle('has-file', !!selected);
        name.textContent = selected ? selected.name : '';
      });
      if (window.lucide) window.lucide.createIcons();
    }

    inputs.forEach(function (input) {
      var card = input.closest('[data-upload-card]');
      input.addEventListener('change', refresh);
      ['dragenter', 'dragover'].forEach(function (eventName) {
        card.addEventListener(eventName, function () { card.classList.add('is-over'); });
      });
      ['dragleave', 'drop'].forEach(function (eventName) {
        card.addEventListener(eventName, function () { card.classList.remove('is-over'); });
      });
    });

    form.addEventListener('submit', function () {
      submit.disabled = true;
      submit.classList.add('is-loading');
      submit.querySelector('[data-submit-label]').textContent = 'Generating PDF...';
      if (window.lucide) window.lucide.createIcons();
    });

    refresh();
  })();
</script>
@endsection
