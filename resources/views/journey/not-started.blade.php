<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $lead->name }} — Journey planner</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/Logo/favicon-32.png') }}">
    @include('partials.theme-head', ['surface' => 'planner'])
</head>
<body>
    <main class="jp-message">
        <img class="logo-light" src="{{ asset('assets/Logo/mark.svg') }}" alt="One Degree Advisory" height="44" style="width:auto">
        <img class="logo-dark" src="{{ asset('assets/Logo/mark-light.svg') }}" alt="" height="44" style="width:auto">
        <h1>{{ $lead->name }}'s planner hasn't been started</h1>
        <p>The One Degree counsellor handling this student sets up the journey planner. It appears here as soon as they open it.</p>
        <a class="jp-btn" href="{{ route('crm.dashboard', ['view' => 'students', 'lead' => $lead->id]) }}">Back to the CRM</a>
    </main>
</body>
</html>
