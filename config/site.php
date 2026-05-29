<?php

return [
    'name' => 'One Degree Advisory',
    'tagline' => 'Global education strategy',

    /* Password for the /admin blog CMS. Override via CMS_PASSWORD in .env. */
    'cms_password' => env('CMS_PASSWORD', 'onedegree'),

    'notice' => 'Spring and Fall 2027 intake planning is open',
    'description' => 'Global education advisory for study abroad planning, university applications, scholarships, visa readiness, and pre-departure support.',

    'contact' => [
        'email' => 'admissions@onedegreeadvisory.com',
        'phone' => '+91 8233365888',
        /* Digits-only form of phone for use in wa.me / sms: links.
           Country code first, no '+' / spaces / dashes. */
        'phone_e164' => '918233365888',
        'address' => 'A-16A, Van Vihar colony, Tonk Road, Jaipur, Rajasthan, 302018',
    ],

    'socials' => [
        [
            'slug'  => 'instagram',
            'label' => 'Instagram',
            'href'  => 'https://www.instagram.com/onedegreeadvisory',
        ],
        [
            'slug'  => 'linkedin',
            'label' => 'LinkedIn',
            'href'  => 'https://www.linkedin.com/company/onedegreeadvisory/',
        ],
    ],

];
