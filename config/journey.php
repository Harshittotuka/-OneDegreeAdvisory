<?php

/*
|--------------------------------------------------------------------------
| Journey planner — documents and essays
|--------------------------------------------------------------------------
|
| Limits for what a student or counsellor can store on a journey plan.
| Files live on the private "local" disk (storage/app/private/journey) and
| are only ever served through the planner's own signed-in download routes.
|
| The upload size here is the app's own limit; PHP's upload_max_filesize and
| post_max_size on the server must be at least this large too, or big files
| are refused before the app sees them.
|
*/

return [
    /*
    |----------------------------------------------------------------------
    | Live updates
    |----------------------------------------------------------------------
    |
    | The planner asks the server every few seconds whether anything on the
    | plan has moved. That question is deliberately cheap — it answers with a
    | fingerprint, not the plan — and the page only fetches the real payload
    | when the fingerprint changes. Set poll_seconds to 0 to switch the whole
    | thing off and leave the refresh button to do the work.
    |
    */
    'live' => [
        'poll_seconds' => 5,
    ],

    /*
    |----------------------------------------------------------------------
    | Meeting reminders
    |----------------------------------------------------------------------
    |
    | Besides the email when a meeting is booked, everyone listed on it —
    | and the student's counsellor — is reminded twice: the day before, and
    | on the day. `journey:meeting-reminders` runs once a day at send_at
    | (India time) and sends both: today's meetings and tomorrow's. Fixed
    | here deliberately rather than read from .env.
    |
    */
    'reminders' => [
        'timezone' => 'Asia/Kolkata',
        'send_at' => '04:00',
    ],

    'documents' => [
        'disk' => 'local',
        'directory' => 'journey',
        'max_upload_kb' => 10240,
        'max_per_plan' => 150,
        'extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
        'essay_max_chars' => 40000,
    ],
];
