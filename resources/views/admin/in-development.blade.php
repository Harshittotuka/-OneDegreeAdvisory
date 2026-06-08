@extends('admin.layout')
@section('title', $title ?? 'In development')

@push('head')
<style>
  .dev-state {
    min-height: 360px;
    display: grid;
    place-items: center;
    padding: 42px 22px;
    text-align: center;
  }
  .dev-state__icon {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    margin: 0 auto 16px;
    border-radius: 16px;
    background: var(--teal-soft);
    color: var(--teal);
  }
  .dev-state__icon i {
    width: 24px;
    height: 24px;
  }
  .dev-state h2 {
    margin: 0 0 6px;
    font-size: 1.35rem;
    letter-spacing: -.01em;
  }
  .dev-state p {
    margin: 0;
    color: var(--muted);
    font-weight: 600;
  }
</style>
@endpush

@section('content')
  <section class="panel dev-state">
    <div>
      <span class="dev-state__icon"><i data-lucide="construction"></i></span>
      <h2>{{ $title ?? 'Section' }} is in development</h2>
      <p>{{ $message ?? 'This section is in development.' }}</p>
    </div>
  </section>
@endsection
