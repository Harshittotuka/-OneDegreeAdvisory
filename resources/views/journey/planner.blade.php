{{--
    The journey planner — one page for three readers. The counsellor edits it
    (inside the CRM), a partner reads it (inside the CRM), and the student opens
    it from their private link. The page is drawn in the browser from the
    payload below (App\Support\JourneyPlanner::payload); every edit is a small
    JSON call, and the server decides what each reader may change.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/Logo/favicon-32.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/journey/planner.css') }}?v={{ filemtime(public_path('assets/journey/planner.css')) }}">
</head>
<body class="jp-mode-{{ $payload['mode'] }}">
    <div id="planner" aria-live="off">
        <noscript><p class="jp-noscript">This planner needs JavaScript switched on.</p></noscript>
    </div>
    <div id="jp-modal"></div>
    <div id="jp-toast" class="jp-toast" role="status" aria-live="polite"></div>
    <script type="application/json" id="jp-payload">@json($payload)</script>
    <script>window.JP_LOGO = @json(asset('assets/Logo/mark.svg')); window.JP_LOGO_DARK = @json(asset('assets/Logo/mark-light.svg'));</script>
    <script src="{{ asset('assets/journey/planner.js') }}?v={{ filemtime(public_path('assets/journey/planner.js')) }}" defer></script>
</body>
</html>
