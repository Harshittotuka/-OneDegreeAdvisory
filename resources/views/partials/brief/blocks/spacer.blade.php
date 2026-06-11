@php
    $sizes = ['sm' => '18px', 'md' => '40px', 'lg' => '72px', 'xl' => '120px'];
    $h = $sizes[$data['size'] ?? 'md'] ?? '40px';
@endphp
<div class="odp-spacer" style="height:{{ $h }}" aria-hidden="true"></div>
