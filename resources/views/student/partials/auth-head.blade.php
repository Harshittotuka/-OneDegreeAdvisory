{{-- Shared <head> for the student sign-in pages: the website's cream theme
     (royal indigo, brand orange, Cormorant headings), with an animated scene
     beside the form. Toasts and the hand-off screen still come from the CRM's
     scripts (crm.js, crm-toast.css). --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex,nofollow">
<meta name="theme-color" content="#1a0088">
<title>{{ $title }} · One Degree Advisory</title>
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/Logo/favicon-32.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600;1,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/student/portal-auth.css') }}?v={{ filemtime(public_path('assets/student/portal-auth.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/crm/crm-toast.css') }}?v={{ filemtime(public_path('assets/crm/crm-toast.css')) }}">
<script src="{{ asset('assets/student/portal-auth.js') }}?v={{ filemtime(public_path('assets/student/portal-auth.js')) }}" defer></script>
