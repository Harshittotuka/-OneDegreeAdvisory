{{-- Shared <head> for the student sign-in pages. The look is the theme the
     student chose (config/themes.php, via partials.theme-head): OneDegree by
     default, the website's cream theme with the animated scene, or Glass.
     Toasts and the hand-off screen still come from the CRM's scripts
     (crm.js, crm-toast.css). --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex,nofollow">
<meta name="theme-color" content="#1a0088">
<title>{{ $title }} · One Degree Advisory</title>
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/Logo/favicon-32.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@include('partials.theme-head', ['surface' => 'auth'])
<link rel="stylesheet" href="{{ asset('assets/crm/crm-toast.css') }}?v={{ filemtime(public_path('assets/crm/crm-toast.css')) }}">
<script src="{{ asset('assets/student/portal-auth.js') }}?v={{ filemtime(public_path('assets/student/portal-auth.js')) }}" defer></script>
