<?php

/*
|--------------------------------------------------------------------------
| Student Profiler — questionnaire definition
|--------------------------------------------------------------------------
| The questions themselves are the EXACT set served by the partner
| gatewayhub profiler (edumilestones engine) for each degree level, captured
| verbatim — same wording, same options, same input types — into questions.json:
|
|   Bachelors = 21 questions   (level 1)
|   Masters   = 25 questions   (level 2)
|   Doctorate = 38 questions   (level 3)
|
| To refresh them, re-pull /student-profiler/get-profiling-questions.php from
| the source and regenerate questions.json; nothing else needs to change.
| This file only adds the degree-card presentation meta (labels, accents).
|
| Field schema (per section → fields[]):
|   type     : radio | chips | select | text | tel
|   key      : stable answer key (derived from the source question id)
|   label    : exact question text
|   options  : exact choices (radio / chips / select)
|   placeholder / required : input hints
*/

$sections = json_decode((string) file_get_contents(__DIR__ . '/questions.json'), true) ?: [];

return [
    'degreeOrder' => ['bachelors', 'masters', 'doctorate'],
    'degrees' => [
        'bachelors' => ['label' => 'Bachelor’s', 'initial' => 'B', 'tag' => 'Undergraduate degree', 'examples' => 'BS · BA · BBA · BEng', 'accent' => 'blue', 'featured' => false],
        'masters'   => ['label' => 'Master’s', 'initial' => 'M', 'tag' => 'Postgraduate degree', 'examples' => 'MS · MBA · MA · MEng', 'accent' => 'orange', 'featured' => true],
        'doctorate' => ['label' => 'Doctorate', 'initial' => 'D', 'tag' => 'PhD & research degree', 'examples' => 'PhD · DBA · EdD', 'accent' => 'gold', 'featured' => false],
    ],
    'sections' => $sections,
];
