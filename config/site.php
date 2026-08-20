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

    /* Decode — the personality assessment app on its own subdomain. The navbar's
       "Evaluate your personality" card opens it in the same tab. Override via
       DECODE_URL if it ever moves. */
    'decode_url' => env('DECODE_URL', 'https://decode.onedegreeadvisory.com/'),

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

    /* Authorization OTP for the Page Builder: saving a page that contains a
       payment section requires a one-time code. The SAME code is emailed to
       every recipient below; entering it correctly authorizes payment-section
       saves for `window_minutes`. This is the gate that stops an unauthorized
       admin from publishing a live payment gateway. Recipients are a
       comma-separated list in PAYMENT_SECTION_OTP_EMAILS. */
    /* Who gets the "payment received" notification (with full enrolment details)
       when a checkout succeeds. Customer also gets a thank-you. Comma-separated. */
    'payment_notify' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PAYMENT_NOTIFY_EMAILS', 'Admissions@onedegreeadvisory.com,seemant@onedegree.com'))
    ))),

    'payment_section_otp' => [
        'recipients' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PAYMENT_SECTION_OTP_EMAILS', 'Admissions@onedegreeadvisory.com,harshittotuka1@gmail.com'))
        ))),
        'mailer' => env('PAYMENT_SECTION_OTP_MAILER', env('CONTACT_FORM_MAILER', 'contact_form')),
        'ttl_minutes' => (int) env('PAYMENT_SECTION_OTP_TTL_MINUTES', 10),
        'window_minutes' => (int) env('PAYMENT_SECTION_OTP_WINDOW_MINUTES', 10),
        'max_attempts' => (int) env('PAYMENT_SECTION_OTP_MAX_ATTEMPTS', 5),
    ],

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
         - careers: application form on the Careers page
         - profiler: Student Profiler (/profiler) — on submit a team
           notification and an applicant thank-you (each carrying the generated
           profile report) are sent from this mailbox.
         - referral: Referral Program (/referral-program) — one submission sends
           THREE emails from this mailbox: the team notification, a confirmation
           to the referrer, and an introduction to the referred student. */
    'forms' => [
        /* Shown whenever an address is malformed OR a placeholder (anything
           containing "example"), which bounces after the relay has already
           accepted it. Server rule, native field validity and every custom
           form script all read this one line so the copy cannot drift. */
        'email_help' => 'Please use a valid email address, or email us at '
            .mb_strtolower(env('CONTACT_FORM_TO', 'Admissions@onedegreeadvisory.com')).'.',

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
        'profiler' => [
            'mailer'    => env('PROFILER_FORM_MAILER', env('CONTACT_FORM_MAILER')),
            'to'        => env('PROFILER_FORM_TO', 'Admissions@onedegreeadvisory.com'),
            'from'      => env('PROFILER_FORM_FROM', 'Admissions@onedegreeadvisory.com'),
            'from_name' => env('PROFILER_FORM_FROM_NAME', 'One Degree Advisory'),
        ],
        'referral' => [
            'mailer'    => env('REFERRAL_FORM_MAILER', env('CONTACT_FORM_MAILER')),
            'to'        => env('REFERRAL_FORM_TO', 'Admissions@onedegreeadvisory.com'),
            'from'      => env('REFERRAL_FORM_FROM', 'Admissions@onedegreeadvisory.com'),
            'from_name' => env('REFERRAL_FORM_FROM_NAME', 'One Degree Advisory'),
            /* Set REFERRAL_NOTIFY_STUDENT=false to stop the introduction email to
               the referred student (the referrer still gets their confirmation and
               the team still gets notified). */
            'notify_student' => filter_var(env('REFERRAL_NOTIFY_STUDENT', true), FILTER_VALIDATE_BOOLEAN),
        ],
    ],

    /* The social row rendered by partials/socials.blade.php — footer, notice
       bar and the contact aside all read this one list, so an icon added here
       appears everywhere at once. Only the http(s) entries are emitted as
       schema.org sameAs (see layouts/app.blade.php); 'call' is a dialer link,
       not a profile. */
    'socials' => [
        [
            'slug'  => 'instagram',
            'label' => 'Instagram',
            'href'  => 'https://www.instagram.com/onedegree.advisory/',
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
        [
            'slug'  => 'call',
            'label' => 'Call',
            /* Opens the device dialer. Number mirrors contact.phone_e164. */
            'href'  => 'tel:+918233365888',
        ],
    ],

];
