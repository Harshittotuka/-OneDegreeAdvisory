@extends('admin.layout')

@section('title', 'Country Visibility')

@push('head')
<style>
  .cv-intro { color: var(--muted); font-size: .9rem; margin: -4px 0 22px; max-width: 760px; }
  .cv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
  .cv-panel { padding: 22px; }
  .cv-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; margin-bottom: 16px; }
  .cv-head h2 { margin: 0 0 3px; font-size: 1.03rem; font-weight: 800; }
  .cv-head p { margin: 0; color: var(--muted); font-size: .82rem; }
  .cv-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 9px; border-radius: 999px;
    background: var(--teal-soft); color: var(--teal-dark); font-size: .74rem; font-weight: 800; white-space: nowrap; }
  .cv-chip i { width: 13px; height: 13px; }
  .cv-list { display: grid; gap: 9px; }
  .cv-country { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 14px;
    padding: 12px 13px; border: 1px solid var(--line); border-radius: 11px; background: #fafbfc; }
  .cv-country-main { min-width: 0; }
  .cv-country-name { display: flex; align-items: center; gap: 9px; font-weight: 800; color: var(--ink); }
  .cv-flag { width: 25px; height: 18px; object-fit: cover; border-radius: 4px; border: 1px solid var(--line); background: #fff; flex-shrink: 0; }
  .cv-country-slug { display: block; margin-top: 2px; color: var(--muted); font-size: .74rem; font-weight: 600; overflow-wrap: anywhere; }
  .cv-toggle { position: relative; display: inline-flex; align-items: center; width: 46px; height: 26px; margin: 0; cursor: pointer; }
  .cv-toggle input { position: absolute; opacity: 0; width: 1px; height: 1px; }
  .cv-toggle span { position: absolute; inset: 0; border-radius: 999px; background: #d8d8e5; transition: background .18s; }
  .cv-toggle span::after { content: ""; position: absolute; width: 20px; height: 20px; top: 3px; left: 3px;
    border-radius: 50%; background: #fff; box-shadow: 0 2px 6px rgba(43,44,64,.2); transition: transform .18s; }
  .cv-toggle input:checked + span { background: var(--teal); }
  .cv-toggle input:checked + span::after { transform: translateX(20px); }
  .cv-empty { color: var(--muted); font-size: .86rem; margin: 0; padding: 10px 0; }
  .cv-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 22px; }
  @media (max-width: 980px) {
    .cv-grid { grid-template-columns: 1fr; }
  }
</style>
@endpush

@section('content')
@php
  $nonMbbsVisible = count(array_filter($nonMbbsCountries, fn ($country) => $country['visible'] ?? true));
  $mbbsVisible = count(array_filter($mbbsCountries, fn ($country) => $country['visible'] ?? true));
@endphp

<p class="cv-intro">
  Choose which country pages should appear on the public site. Hidden countries are removed from menus and listings, and their direct frontend URLs return 404 until you show them again.
</p>

<form method="POST" action="{{ route('admin.country-visibility.update') }}">
  @csrf

  <div class="cv-grid">
    <section class="panel cv-panel">
      <div class="cv-head">
        <div>
          <h2>Non-MBBS countries</h2>
          <p>Study-abroad destination pages synced from Leverage Edu.</p>
        </div>
        <span class="cv-chip"><i data-lucide="eye"></i> {{ $nonMbbsVisible }}/{{ count($nonMbbsCountries) }} live</span>
      </div>

      <div class="cv-list">
        @forelse($nonMbbsCountries as $country)
          <div class="cv-country">
            <div class="cv-country-main">
              <span class="cv-country-name">
                @if(! empty($country['flag']))
                  <img class="cv-flag" src="https://flagcdn.com/w40/{{ strtolower($country['flag']) }}.png" alt="">
                @endif
                {{ $country['name'] }}
              </span>
              <span class="cv-country-slug">/countries/{{ $country['slug'] }}</span>
            </div>
            <label class="cv-toggle" title="Show on frontend">
              <input type="checkbox"
                     name="visible[{{ \App\Support\CountryVisibilityStore::GROUP_NON_MBBS }}][]"
                     value="{{ $country['slug'] }}"
                     @checked($country['visible'] ?? true)>
              <span></span>
            </label>
          </div>
        @empty
          <p class="cv-empty">No non-MBBS country data found yet.</p>
        @endforelse
      </div>
    </section>

    <section class="panel cv-panel">
      <div class="cv-head">
        <div>
          <h2>MBBS countries</h2>
          <p>MBBS country pages synced from AV Global Overseas.</p>
        </div>
        <span class="cv-chip"><i data-lucide="eye"></i> {{ $mbbsVisible }}/{{ count($mbbsCountries) }} live</span>
      </div>

      <div class="cv-list">
        @forelse($mbbsCountries as $country)
          <div class="cv-country">
            <div class="cv-country-main">
              <span class="cv-country-name">
                @if(! empty($country['flag_url']))
                  <img class="cv-flag" src="{{ $country['flag_url'] }}" alt="">
                @elseif(! empty($country['flag']))
                  <img class="cv-flag" src="https://flagcdn.com/w40/{{ strtolower($country['flag']) }}.png" alt="">
                @endif
                {{ $country['name'] }}
              </span>
              <span class="cv-country-slug">/mbbs/country/{{ $country['slug'] }}</span>
            </div>
            <label class="cv-toggle" title="Show on frontend">
              <input type="checkbox"
                     name="visible[{{ \App\Support\CountryVisibilityStore::GROUP_MBBS }}][]"
                     value="{{ $country['slug'] }}"
                     @checked($country['visible'] ?? true)>
              <span></span>
            </label>
          </div>
        @empty
          <p class="cv-empty">No MBBS country data found yet.</p>
        @endforelse
      </div>
    </section>
  </div>

  <div class="cv-actions">
    <button type="submit" class="btn btn-primary"><i data-lucide="save" style="width:16px;height:16px;"></i> Save visibility</button>
    <a class="btn btn-ghost" href="{{ route('home') }}" target="_blank"><i data-lucide="external-link" style="width:16px;height:16px;"></i> Preview site</a>
  </div>
</form>
@endsection
