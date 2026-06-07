<?php

return [
    'name' => 'One Degree Advisory',
    'tagline' => 'Global education strategy',

    /* Password for the /admin blog CMS. Override via CMS_PASSWORD in .env. */
    'cms_password' => env('CMS_PASSWORD', 'onedegree'),

    /*
     * Keep the About-page CMS hidden until it is ready for client use.
     * Set ABOUT_CMS_ENABLED=true locally to re-enable the existing editor.
     */
    'about_cms_enabled' => env('ABOUT_CMS_ENABLED', false),

    'notice' => 'Spring and Fall 2027 intake planning is open',

    /* Scrolling announcement marquee shown in the top blue notice bar.
       Each item displays only its first four words followed by "....." as a
       teaser; the full text is the link tooltip. 'route' is a named route;
       fall back to 'href' for an absolute/external URL. */
    'notices' => [
        ['text' => 'Spring and Fall 2027 intake planning is open now', 'route' => 'contact'],
        ['text' => 'MBBS abroad seats filling fast for the 2027 batch', 'route' => 'mbbs.student'],
        ['text' => 'Book your IELTS, SAT and ACT test-prep slots', 'route' => 'services.test-prep'],
    ],

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
            'slug'  => 'facebook',
            'label' => 'Facebook',
            'href'  => 'https://www.facebook.com/onedegreeadvisory',
        ],
        [
            'slug'  => 'linkedin',
            'label' => 'LinkedIn',
            'href'  => 'https://www.linkedin.com/company/onedegreeadvisory/',
        ],
    ],

];
