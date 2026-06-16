<?php

return [
    'name' => 'One Degree Advisory',
    'tagline' => 'Global education strategy',

    /* The one true public host for the production site. ONLY requests to this
       host are allowed to be indexed; every other host (the nip.io UAT box, the
       raw server IP, a hosting preview domain, or localhost) is treated as a
       non-canonical mirror and served noindex + Disallow:/ so it can never
       compete with the live site as duplicate content. This is deliberately a
       fixed value rather than derived from APP_URL, because each environment
       sets its own APP_URL (the test box's APP_URL is the nip.io host) and
       would otherwise consider itself canonical. Override via CANONICAL_HOST. */
    'canonical_host' => env('CANONICAL_HOST', 'onedegreeadvisory.com'),

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
        /* Structured form of the address above — used to emit a schema.org
           PostalAddress in the Organization JSON-LD (richer than a plain string,
           helps local / "study abroad consultant in Jaipur" relevance). */
        'address_parts' => [
            'street'      => 'A-16A, Van Vihar Colony, Tonk Road',
            'locality'    => 'Jaipur',
            'region'      => 'Rajasthan',
            'postal_code' => '302018',
            'country'     => 'IN',
        ],
        /* Approximate geo-coordinates of the Jaipur office (Van Vihar Colony,
           Tonk Road). Emitted as schema.org "geo" on the LocalBusiness node so
           Google can place the business on the map / in local results — a key
           signal that this is a Jaipur education firm, distinct from the
           similarly-named US financial advisor. Replace lat/lng with the exact
           values from the Google Business Profile pin once it is verified. */
        'geo' => [
            'lat' => '26.8478',
            'lng' => '75.8073',
        ],
        /* Link to the Google Maps place once the Business Profile exists. Set
           via GOOGLE_MAPS_PLACE_URL in .env; emitted as schema.org "hasMap". */
        'maps_url' => env('GOOGLE_MAPS_PLACE_URL'),
    ],

    /* Services offered — emitted as a schema.org OfferCatalog on the Organization
       and mirrored by the visible cards on /study-abroad. Editing this list keeps
       the structured data and the page in step. */
    'services' => [
        ['name' => 'Course & Career Mapping', 'description' => 'Match interests, marks, and career goals to the right country and degree programs abroad.'],
        ['name' => 'University Shortlisting', 'description' => 'Ambitious, realistic, and secure university lists built on admissions fit, cost, and outcomes.'],
        ['name' => 'Applications & Essays', 'description' => 'SOPs, personal statements, and recommendations shaped into one coherent application.'],
        ['name' => 'Scholarship & Finance Planning', 'description' => 'Scholarships, assistantships, and education-loan readiness compared before you decide.'],
        ['name' => 'Student Visa Counselling', 'description' => 'Documents, interview prep, and compliance for student visas to every major destination.'],
        ['name' => 'Test Preparation', 'description' => 'IELTS, TOEFL, PTE, SAT, ACT, GRE, and GMAT preparation aligned to your application timeline.'],
        ['name' => 'Pre & Post Departure Support', 'description' => 'Accommodation, banking, insurance, and the first 30 days of settling in abroad.'],
    ],

    /* Topical expertise — emitted as schema.org "knowsAbout". Signals to search
       engines the subjects and destinations this site is authoritative on, which
       supports "study abroad" and "study in <country>" style queries. */
    'expertise' => [
        'Study abroad', 'Overseas education', 'Study abroad consultancy',
        'University admissions', 'Student visa', 'Scholarships',
        'IELTS', 'TOEFL', 'SAT', 'GRE', 'GMAT', 'MBBS abroad',
        'Study in USA', 'Study in UK', 'Study in Canada', 'Study in Australia',
        'Study in Ireland', 'Study in Germany', 'Study in France', 'Study in New Zealand',
        'Study in Italy', 'Study in Netherlands', 'Study in Finland', 'Study in Spain',
        'Study in Dubai', 'Study in Europe',
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
