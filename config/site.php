<?php

return [
    'name' => 'One Degree Advisory',
    'tagline' => 'Global education strategy',

    /* Password for the /admin blog CMS. Override via CMS_PASSWORD in .env. */
    'cms_password' => env('CMS_PASSWORD', 'onedegree'),

    /* Super-admin password (the "infolith" login). Same /admin login form, but
       this password unlocks every CMS page — including ones hidden from the
       standard editor, such as the About page. Override via SUPER_ADMIN_PASSWORD. */
    'super_admin_password' => env('SUPER_ADMIN_PASSWORD', 'infolith@123'),

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

    /* Where the public website forms deliver to, and which mailbox the team
       notification + applicant confirmation are sent from. Override per-env
       in .env so the addresses are not hard-coded.
         - contact: enquiry form on the Contact + Home pages
         - careers: application form on the Careers page */
    'forms' => [
        'contact' => [
            'mailer'    => env('CONTACT_FORM_MAILER'),
            'to'        => env('CONTACT_FORM_TO', 'Admissions@onedegreeadvisory.com'),
            'from'      => env('CONTACT_FORM_FROM', 'Admissions@onedegreeadvisory.com'),
            'from_name' => env('CONTACT_FORM_FROM_NAME', 'One Degree Advisory'),
        ],
        'careers' => [
            'mailer'    => env('CAREERS_FORM_MAILER'),
            'to'        => env('CAREERS_FORM_TO', 'Smita@onedegreeadvisory.com'),
            'from'      => env('CAREERS_FORM_FROM', 'Smita@onedegreeadvisory.com'),
            'from_name' => env('CAREERS_FORM_FROM_NAME', 'One Degree Advisory · Careers'),
        ],
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
        [
            'slug'  => 'whatsapp',
            'label' => 'WhatsApp',
            /* Number mirrors contact.phone_e164 (digits only, country code first). */
            'href'  => 'https://wa.me/918233365888',
        ],
    ],

];
