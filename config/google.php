<?php

/*
|--------------------------------------------------------------------------
| Google — real Meet rooms from each counsellor's own account
|--------------------------------------------------------------------------
|
| A counsellor connects their Google account once (CRM → planner → Meetings →
| "Connect Google"). After that, choosing Google Meet on a meeting creates a
| real room in *their* Google Calendar, with no guests on it — Google sends no
| invitation; the planner's own email carries the link.
|
| Only the OAuth client's ID and secret come from .env: they are credentials
| and differ per Google Cloud project. Everything else is policy and is
| deliberately fixed here, so a deploy carries it.
|
| Keeping a connection alive for good needs three things, two of them here:
|   1. The OAuth consent screen is "In production", not "Testing" — Google
|      expires a Testing app's refresh tokens after 7 days. (Google Cloud
|      console; nothing in code can change it.)
|   2. Offline access with a refresh token, stored encrypted and never thrown
|      away when Google leaves it out of a later response.
|   3. The token is used regularly — Google drops one left unused for six
|      months. The scheduler refreshes every connection daily, and opening the
|      CRM does it too on a server with no cron (keep_alive_days).
|
*/

return [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),

    // Leave unset to use this site's own /crm/google/callback (always https
    // outside localhost). It must match an "Authorised redirect URI" on the
    // OAuth client exactly.
    'redirect' => env('GOOGLE_REDIRECT_URI'),

    // Calendar events is the narrowest scope that can create a Meet room on a
    // personal Gmail account as well as a Workspace one. openid + email tell
    // the CRM which account was connected.
    'scopes' => [
        'openid',
        'email',
        'https://www.googleapis.com/auth/calendar.events',
    ],

    // Meeting times are typed in India time; this tells Google so.
    'timezone' => 'Asia/Kolkata',

    // Refresh an access token this long before Google says it expires.
    'refresh_early_seconds' => 300,

    // Opening the CRM refreshes a connection not refreshed for this long, so
    // a token stays in use even where no scheduler runs.
    'keep_alive_days' => 3,

    // A room made for a meeting dialog that was then closed without saving is
    // removed from the counsellor's calendar after this long.
    'unclaimed_room_hours' => 12,

    'timeout' => 15,
];
