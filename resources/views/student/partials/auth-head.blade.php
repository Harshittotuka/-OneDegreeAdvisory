{{-- Shared <head> for the student sign-in pages: the CRM's own login look (the
     Evergreen theme), so students get the same polished screen as the team. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>{{ $title }} · One Degree Advisory</title>
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/Logo/favicon-32.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500..700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/crm/crm.css') }}?v={{ filemtime(public_path('assets/crm/crm.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/crm/crm-toast.css') }}?v={{ filemtime(public_path('assets/crm/crm-toast.css')) }}">
