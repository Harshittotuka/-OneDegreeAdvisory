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
    'documents' => [
        'disk' => 'local',
        'directory' => 'journey',
        'max_upload_kb' => 10240,
        'max_per_plan' => 150,
        'extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
        'essay_max_chars' => 40000,
    ],
];
