<?php

namespace App\Support;

/**
 * Seed content for the Brief Page Builder — the four hand-built pages translated
 * into block/section data so they render through the CMS pipeline identically and
 * become editable examples. Written to storage/app/brief-pages.json on first read.
 */
class BriefPageContent
{
    /** Wrap a flat list of strings into repeater rows: ['a','b'] → [['text'=>'a'],['text'=>'b']]. */
    private function li(array $items): array
    {
        return array_map(fn ($t) => ['text' => $t], $items);
    }

    private function sec(string $id, string $type, array $data): array
    {
        return ['id' => $id, 'type' => $type, 'visible' => true, 'data' => $data];
    }

    public function defaults(): array
    {
        return [
            $this->europe(),
            $this->newZealand(),
            $this->medicine(),
            $this->wednesday(),
        ];
    }

    /* ───────────────────────── Europe ───────────────────────── */
    private function europe(): array
    {
        return [
            'slug' => 'europe',
            'path' => '/europe',
            'title' => 'Europe Packages',
            'page_title' => 'Europe Study Abroad Packages | One Degree Advisory',
            'meta_description' => 'Transparent study-abroad packages for public universities in Europe — admission strategy, applications, documentation, and visa support, with an Admission Guarantee track.',
            'visible' => true,
            'content_versions' => [
                'razorpay_payment_block' => 1,
                'razorpay_pricing_links' => 2,
            ],
            'sections' => [
                $this->sec('hero', 'hero', [
                    'eyebrow' => 'Europe advisory packages',
                    'title' => 'One degree closer to your dream university in Europe.',
                    'copy' => 'Transparent, expert-led packages for public universities across Europe — from admission strategy and documentation to visa filing and arrival.',
                    'actions' => [
                        ['label' => 'Book a consultation', 'icon' => 'calendar-check', 'href' => '/contact', 'style' => 'secondary'],
                    ],
                    'panel_heading' => 'Built for public university applications in Europe.',
                    'panel_items' => [
                        ['icon' => 'map', 'text' => 'Country-fit strategy before applications begin.'],
                        ['icon' => 'file-check-2', 'text' => 'SOP, LOR, resume, application and visa support.'],
                        ['icon' => 'shield-check', 'text' => 'Admission Guarantee track available with Infinity.'],
                    ],
                ]),
                $this->sec('destinations', 'dest_strip', [
                    'label' => '🌍 Top Study Destinations in Europe',
                    'items' => [
                        ['code' => 'de', 'name' => 'Germany'],
                        ['code' => 'fr', 'name' => 'France'],
                        ['code' => 'nl', 'name' => 'Netherlands'],
                        ['code' => 'it', 'name' => 'Italy'],
                        ['code' => 'pl', 'name' => 'Poland'],
                        ['code' => 'at', 'name' => 'Austria'],
                        ['code' => 'se', 'name' => 'Sweden'],
                        ['code' => 'lv', 'name' => 'Latvia'],
                        ['code' => 'lt', 'name' => 'Lithuania'],
                        ['code' => 'fi', 'name' => 'Finland'],
                        ['code' => '', 'name' => 'More Destinations'],
                    ],
                ]),
                $this->sec('journey', 'journey', [
                    'title' => '✈️ Start Your Study Abroad Journey with One Degree Advisory',
                    'layout' => 'balanced',
                    'steps' => [
                        ['label' => 'Step 1', 'heading' => 'Admission Strategy & Shortlisting', 'items' => [
                            ['name' => 'Student Profile Analysis', 'desc' => 'Discuss your preferences, get expert guidance, answers to your questions, and a personalized study abroad roadmap.'],
                            ['name' => 'University Shortlisting', 'desc' => 'Our experts will provide a personalized list of universities best matched to your profile and career goals.'],
                            ['name' => 'University Finalization Session', 'desc' => 'Discuss your shortlisted universities and finalize the best options for your applications.'],
                        ]],
                        ['label' => 'Step 2', 'heading' => 'Application, SOP & Documentation Support', 'items' => [
                            ['name' => 'SOP, LOR & Resume Building Support', 'desc' => 'Access premium templates and professional editing support to craft impactful SOPs, LORs, and resumes tailored to your profile.'],
                            ['name' => 'Application Submission Support', 'desc' => 'You’re nearly there — our expert team will take care of your application submission from here.'],
                        ]],
                        ['label' => 'Step 3', 'heading' => 'Visa Application Filing & Interview Preparation', 'items' => [
                            ['name' => 'Visa Filing', 'desc' => 'Once your admission offer is received, a visa expert will review your documents.'],
                            ['name' => 'Visa Interview Preparation', 'desc' => 'Prepare for your interview with our expert visa counsellors.'],
                        ]],
                    ],
                    'final_title' => 'Fly to Your Dream University!',
                    'final_body' => 'You’re ready to take off! The One Degree community is excited to welcome you to your dream college.',
                ]),
                $this->sec('vouchers', 'vouchers', [
                    'title' => '🎁 Refer & Earn — Student Vouchers',
                    'subtitle' => 'Refer a friend and earn credits when they enrol. The more they grow, the more you earn!',
                    'cards' => [
                        ['tier' => 'Explorer', 'icon' => '🌍', 'amount' => '₹2,500', 'variant' => 'explorer', 'badge' => ''],
                        ['tier' => 'Achiever', 'icon' => '🏆', 'amount' => '₹5,000', 'variant' => 'achiever-r', 'badge' => '⭐ Popular'],
                        ['tier' => 'Infinity', 'icon' => '♾️', 'amount' => '₹10,000', 'variant' => 'infinity', 'badge' => ''],
                    ],
                ]),
                $this->sec('packages', 'pricing', [
                    'heading' => 'Europe Study Abroad Packages for Public University',
                    'sub' => 'Choose the plan that fits your ambition',
                    'enrol_href' => '#europe-payment-option-0',
                    'plans' => [
                        ['variant' => 'starter', 'name' => 'Explorer', 'badge' => 'Explorer', 'price' => '₹54,999 + GST', 'desc' => 'Perfect for students applying to one European country.', 'btn_href' => '#europe-payment-option-0', 'features' => $this->li([
                            'Profile Evaluation', 'University Shortlisting', 'Document Preparation Assistance',
                            'Country-specific Doc Guidance (APS, Blocked Account etc.)', 'Interview Assistance',
                            'Application Assistance', 'Visa Assistance', 'Pre-departure Guidance', 'Counsellor Support',
                            'IELTS Preparation Included', 'Education Loan Assistance',
                        ])],
                        ['variant' => 'achiever', 'name' => 'Achiever', 'badge' => '⭐ Most Popular', 'price' => '₹69,999+ GST', 'desc' => 'Best for students targeting multiple European countries. (Access to 2 European Countries)', 'btn_href' => '#europe-payment-option-1', 'features' => $this->li([
                            'Everything in Explorer', 'Priority Counsellor Support',
                            'Maximize Your Chances with Multi-Country Admission Support.',
                        ])],
                        ['variant' => 'elite', 'name' => 'Infinity', 'badge' => '♾️ Infinity', 'price' => '₹99,999 + GST', 'desc' => 'Admission Opportunities Across Europe, Centered on Your First-Choice Destination*', 'btn_href' => '#europe-payment-option-2', 'features' => [
                            ['text' => 'Everything in Achiever, Explore <strong>Up to 5 European Countries</strong>'],
                            ['text' => 'Priority Counsellor Support'],
                            ['text' => 'Student–Alumni Interaction'],
                        ]],
                    ],
                ]),
                self::europePaymentBlock(),
                $this->sec('disclaimer', 'disclaimer', [
                    'heading' => '⚠️ Not Included — Paid Directly by Student',
                    'items' => [
                        ['text' => 'IELTS / PTE / language exam fees.'],
                        ['text' => 'University application fees (varies by country/university).'],
                        ['text' => 'Visa fees (Schengen or country-specific).'],
                        ['text' => 'APS certificate fee (Germany).'],
                        ['text' => 'Blocked account deposit (Germany).'],
                        ['text' => 'HRD apostille / document legalisation charges.'],
                        ['text' => 'CIMEA evaluation fees (Italy).'],
                        ['text' => 'DOV or equivalent authentication fees.'],
                        ['text' => 'Any consulate or embassy service charges.'],
                        ['text' => 'Infinity - Guaranteed admission opportunities with the flexibility to explore multiple European destinations while prioritizing your preferred study destination. *'],
                        ['text' => "For all Public University packages, the consultancy service fee is non-refundable after the student's enrollment has been completed."],
                        ['text' => '<strong>Additional expenses shall be borne by the student.</strong>'],
                    ],
                ]),
            ],
        ];
    }

    /** Default secure-payment section, also used for the one-time CMS data upgrade. */
    public static function europePaymentBlock(): array
    {
        return [
            'id' => 'europe-payment',
            'type' => 'payment',
            'visible' => true,
            'data' => [
                'eyebrow' => 'Secure online enrolment',
                'title' => 'Choose your Europe advisory package.',
                'description' => 'Select a plan, enter the student details, and request approval. Razorpay Checkout opens only after the admissions team confirms the request with a one-time code.',
                'layout' => 'split',
                'options' => [
                    ['label' => 'Explorer', 'amount' => '54999', 'description' => 'One European country', 'badge' => 'Starter'],
                    ['label' => 'Achiever', 'amount' => '69999', 'description' => 'Access to two European countries', 'badge' => 'Most popular'],
                    ['label' => 'Infinity', 'amount' => '99999', 'description' => 'Explore up to five European countries', 'badge' => 'Premium'],
                ],
                'button_label' => 'Enrol & Pay',
                'note' => 'Taxes or statutory charges are confirmed separately by admissions. Never share card, UPI PIN or banking credentials with anyone.',
                'surface' => 'card',
                'accent' => '#f05a28',
                'accent2' => '#2b1fa8',
            ],
        ];
    }

    /* ───────────────────────── New Zealand ───────────────────────── */
    private function newZealand(): array
    {
        return [
            'slug' => 'destination-new-zealand',
            'path' => '/destination-new-zealand',
            'title' => 'Destination Update: New Zealand',
            'page_title' => 'Destination Update: New Zealand Graduate Work Visa | One Degree Advisory',
            'meta_description' => 'New Zealand launches a Short-term Graduate Work Visa from 16 November 2026 and extends Post Study Work Visa eligibility for Level 7 Graduate Diploma holders.',
            'visible' => true,
            'sections' => [
                $this->sec('hero', 'hero', [
                    'eyebrow' => 'Destination Update · New Zealand',
                    'title' => 'New Zealand opens new graduate work pathways.',
                    'copy' => 'New Zealand is introducing major post-study work improvements for international graduates. A new Short-term Graduate Work Visa launches 16 November 2026 — providing eligible graduates up to 6 months of open work rights to explore career opportunities and transition into skilled employment.',
                    'actions' => [
                        ['label' => 'Plan your NZ pathway', 'icon' => 'calendar-check', 'href' => '/contact', 'style' => 'primary'],
                    ],
                    'panel_heading' => "What's new for graduates.",
                    'panel_items' => [
                        ['icon' => 'briefcase', 'text' => '6-month Short-term Graduate Work Visa.'],
                        ['icon' => 'graduation-cap', 'text' => 'Extended PSWV for Level 7 Graduate Diploma.'],
                        ['icon' => 'route', 'text' => 'Clear study-to-employment pathway.'],
                        ['icon' => 'trending-up', 'text' => 'Growing demand for skilled professionals.'],
                    ],
                ]),
                $this->sec('banner', 'country_banner', [
                    'flag' => 'https://flagcdn.com/nz.svg',
                    'kicker' => '🇳🇿 Headline Alert',
                    'heading' => 'New Zealand launches a Short-term Graduate Work Visa from 16 November 2026.',
                ]),
                $this->sec('action', 'callout', [
                    'icon' => 'zap',
                    'label' => 'Action',
                    'body' => 'Activate your New Zealand pipeline now. Frame the Short-term Graduate Work Visa as a stepping stone to skilled employment, and highlight extended Post Study Work Visa eligibility for Level 7 Graduate Diploma holders. November 2026 is the target window — advise students now.',
                ]),
                $this->sec('highlights', 'brief_cards', [
                    'label' => '📌 Key Policy Highlights',
                    'cards' => [
                        ['title' => 'New 6-Month Short-term Graduate Work Visa — 16 November 2026', 'level' => 'high', 'body' => 'New Zealand is launching a dedicated Short-term Graduate Work Visa granting eligible graduates up to 6 months of open work rights. This new pathway helps graduates bridge the gap between completing studies and securing skilled employment — addressing the most common post-study concern for international students.', 'tags' => $this->li(['New Zealand', 'Post-Study Work'])],
                        ['title' => 'Extended Post Study Work Visa for Level 7 Graduate Diploma Holders', 'level' => 'high', 'body' => 'Certain Level 7 Graduate Diploma holders will now qualify for an extended Post Study Work Visa — a significant expansion of work rights previously limited to higher-level qualifications. This makes New Zealand far more attractive for students pursuing Level 7 diploma programmes.', 'tags' => $this->li(['New Zealand', 'Level 7'])],
                        ['title' => 'Better Pathways from Study to Employment', 'level' => 'medium', 'body' => 'The new changes introduce smoother, structured transitions from study to employment. Students can now plan their qualification-to-work pathway with much greater certainty — making New Zealand more competitive against the UK, Canada, and Australia.', 'tags' => $this->li(['New Zealand'])],
                        ['title' => "Stronger Alignment with New Zealand's Workforce Needs", 'level' => 'medium', 'body' => "These visa changes strategically align graduate skills with New Zealand's growing demand for qualified professionals. The policy signals a long-term government commitment to retaining international talent — a strong counselling point for students weighing long-term career and residency pathways.", 'tags' => $this->li(['New Zealand', 'Workforce'])],
                    ],
                ]),
                $this->sec('why', 'pitch', [
                    'label' => '🌏 Why New Zealand — The ODA Advantage',
                    'heading' => 'New Zealand is rapidly emerging as a top study destination.',
                    'intro' => 'New Zealand offers a combination few destinations can match: globally recognised qualifications, excellent education standards, a safe and welcoming environment, and now — with these changes — even stronger post-study work opportunities.',
                    'columns' => [
                        ['heading' => 'Destination Strengths', 'items' => $this->li(['Globally recognised qualifications', 'Excellent education standards', 'Safe, welcoming environment', 'High quality of life'])],
                        ['heading' => 'New Visa Benefits', 'items' => $this->li(['6-month Short-term Graduate Work Visa', 'Extended PSWV for Level 7 Grad Diploma', 'Clear study-to-employment pathway', 'Growing demand for skilled professionals'])],
                    ],
                ]),
                $this->sec('radar', 'split', [
                    'label' => '📡 Visa Radar & Expert Insight',
                    'cards' => [
                        ['heading' => 'Visa Radar — New Zealand', 'body' => '', 'tone' => '', 'items' => [
                            ['text' => '<strong>Short-term Graduate Work Visa</strong> — 6-month open work rights; launching 16 Nov 2026.'],
                            ['text' => '<strong>Post Study Work Visa (Extended)</strong> — now includes certain Level 7 Graduate Diploma holders.'],
                            ['text' => '<strong>Study Visa</strong> — individual profile assessment; plan early.'],
                        ]],
                        ['heading' => 'Expert Insight', 'tone' => '', 'body' => "New Zealand student visa decisions are assessed on an individual profile basis. Entry requirements, financial documentation, and visa outcomes vary by the student's academic background, career progression, and financial profile. ODA's NZ specialists — with 7+ years helping students secure admissions and visas — provide personalised advice to maximise success.", 'items' => []],
                    ],
                ]),
                $this->sec('sources', 'sources', [
                    'label' => 'Sources',
                    'links' => [
                        ['text' => '1. Immigration New Zealand — New and Updated Post-Study Work Visa Options', 'href' => 'https://www.immigration.govt.nz/about-us/news-centre/new-and-updated-post-study-work-visa-options/'],
                        ['text' => '2. Times of India — NZ Expands Student Visa Work Hours & Post-Study Opportunities', 'href' => 'https://timesofindia.indiatimes.com/education/study-abroad/new-zealand-expands-student-visa-work-hours-and-poststudy-opportunities-for-international-students-heres-what-to-know/articleshow/125227799.cms'],
                    ],
                ]),
                $this->sec('talk', 'talk', [
                    'label' => '💬 Counsellor Talking Points',
                    'quoted' => true,
                    'items' => $this->li([
                        "New Zealand is giving you something new — a dedicated graduate work visa right after graduation, with open work rights. You're not scrambling for employer sponsorship from day one. You have breathing room to find the right role.",
                        "If you're considering a Level 7 Graduate Diploma, New Zealand just became far more attractive. The extended Post Study Work Visa eligibility means your diploma now opens doors it simply didn't before — act on this before the November 2026 launch.",
                        "Every New Zealand visa application is assessed individually. Your academic background, career progression, and financial profile all matter. ODA's specialists have 7+ years of experience — let us build your strongest possible application.",
                    ]),
                ]),
                $this->sec('tip', 'tip', [
                    'surface' => 'card',
                    'kicker' => '📍 Student Tip',
                    'body' => "If you're planning to study abroad, now is a great time to explore New Zealand. Choosing the right course can provide a world-class education and open doors to valuable work experience and long-term career prospects in a thriving economy.",
                ]),
                $this->sec('cta', 'cta_band', [
                    'heading' => 'Thinking about New Zealand for 2026?',
                    'body' => "Book a free personalised session with ODA. We'll map your profile, budget, and timeline to the right New Zealand qualification and the new graduate work pathways.",
                    'btn_label' => 'Book a free consultation', 'btn_icon' => 'calendar-check', 'btn_href' => '/contact',
                ]),
            ],
        ];
    }

    /* ───────────────────────── Medicine & Beyond ───────────────────────── */
    private function medicine(): array
    {
        return [
            'slug' => 'medicine-and-beyond',
            'path' => '/medicine-and-beyond',
            'title' => 'Medicine & Beyond',
            'page_title' => 'Medicine & Beyond: Alternative Pathways for MBBS Aspirants 2026 | One Degree Advisory',
            'meta_description' => 'A complete, honest guide to every real pathway into medicine and healthcare for 2026 — MBBS abroad, private MBBS India, BDS, AYUSH, Nursing & Allied Health, and Pharmacy.',
            'visible' => true,
            'sections' => [
                $this->sec('hero', 'hero', [
                    'eyebrow' => 'Medicine Edition · Pathways 2026',
                    'title' => 'Your MBBS dream has more than one road.',
                    'copy' => 'Re-NEET is on June 21, with results following in weeks. Whether your score is high, average, or lower than expected — there is a valid, respected pathway into medicine or healthcare for you. This guide maps every real option clearly and honestly.',
                    'actions' => [
                        ['label' => 'Map my pathway', 'icon' => 'stethoscope', 'href' => '/contact', 'style' => 'primary'],
                    ],
                    'panel_heading' => 'Six real pathways into medicine.',
                    'panel_items' => [
                        ['icon' => 'plane-takeoff', 'text' => 'MBBS Abroad — NMC-approved, ₹20–40L.'],
                        ['icon' => 'hospital', 'text' => 'Private MBBS in India.'],
                        ['icon' => 'smile', 'text' => 'BDS — the closest alternative to MBBS.'],
                        ['icon' => 'leaf', 'text' => 'AYUSH — BAMS, BHMS, BUMS, BSMS.'],
                        ['icon' => 'heart-pulse', 'text' => 'Nursing & Allied Health.'],
                        ['icon' => 'pill', 'text' => 'Pharmacy — B.Pharm / Pharm.D.'],
                    ],
                ]),
                $this->sec('pathmap', 'card_grid', [
                    'label' => '🗺️ The Pathway Map — At a Glance',
                    'cards' => [
                        ['emoji' => '🏛️', 'title' => 'Govt. MBBS India', 'meta' => '560+ marks · AIQ + State quota · ₹1–2L total fees', 'body' => ''],
                        ['emoji' => '🏥', 'title' => 'Private MBBS India', 'meta' => '300–550 marks · Management quota · ₹60–120L total', 'body' => ''],
                        ['emoji' => '✈️', 'title' => 'MBBS Abroad', 'meta' => 'Qualifying score · NMC-approved · ₹20–40L total', 'body' => ''],
                        ['emoji' => '🌿', 'title' => 'Allied & AYUSH', 'meta' => 'Any NEET score · BDS, BAMS, BPT, Nursing · ₹5–30L', 'body' => ''],
                    ],
                ]),
                // Path 1 — MBBS Abroad
                $this->sec('p1-head', 'heading', ['surface' => 'card', 'label' => "✈️ Path 1 — MBBS Abroad (ODA's Core Strength)", 'heading' => 'Who is this for?', 'sub' => 'Students who qualify NEET (even with a low rank), cannot secure an affordable India seat, or want a full medical degree at significantly lower cost than Indian private colleges. Total cost: ₹20–40L for the entire 6-year programme — vs ₹60–120L in Indian private colleges.']),
                $this->sec('p1-countries', 'card_grid', ['label' => '', 'cards' => [
                    ['emoji' => '🇷🇺', 'title' => 'Russia', 'meta' => '₹20–30L total', 'body' => 'Largest destination for Indian medical students · 50+ NMC-approved govt. universities · Sept 2026 intake open'],
                    ['emoji' => '🇬🇪', 'title' => 'Georgia', 'meta' => '₹25–35L total', 'body' => 'Lowest-cost European option · English medium · EU-standard education · NMC-approved · Sept 2026 open'],
                    ['emoji' => '🇺🇿', 'title' => 'Uzbekistan', 'meta' => '₹22–32L total', 'body' => 'Rapidly growing · NMC-compliant · Affordable living costs · Sept 2026 open'],
                ]]),
                $this->sec('p1-split', 'split', ['label' => '', 'cards' => [
                    ['heading' => '✅ You must know', 'tone' => 'good', 'body' => "NEET qualifying score is mandatory (you don't need a high rank — just qualify). Choose only NMC-approved universities. The course must be 54 months + a 12-month internship at the same university, in English.", 'items' => []],
                    ['heading' => '⚠️ Critical warning', 'tone' => 'warn', 'body' => 'After graduating, you must pass the FMGE/NEXT exam to practice in India. Ask ODA for FMGE pass-rate data by university before committing — this is the single most important metric.', 'items' => []],
                ]]),
                // Path 2 — Private MBBS India
                $this->sec('p2-head', 'heading', ['surface' => 'card', 'label' => '🏥 Path 2 — Private MBBS in India', 'heading' => 'Who is this for?', 'sub' => 'Students with a NEET score between 300–550 who want to study in India and whose families can invest ₹60–120L. Students with ranks beyond 6–8 lakh may find it very difficult to secure an affordable private MBBS seat — this is where MBBS abroad becomes more attractive.']),
                $this->sec('p2-split', 'split', ['label' => '', 'cards' => [
                    ['heading' => 'Seat types available', 'tone' => '', 'body' => '', 'items' => [
                        ['text' => '<strong>AIQ seats</strong> (15%) — All India Quota via MCC counselling'],
                        ['text' => '<strong>State quota</strong> (85%) — State counselling, domicile-based'],
                        ['text' => '<strong>Management quota</strong> (15–25%) — Direct, higher fees'],
                        ['text' => '<strong>NRI quota</strong> — For NRI/OCI/PIO candidates'],
                    ]],
                    ['heading' => '2026 updates', 'tone' => '', 'body' => 'India added roughly 15,000 new MBBS seats for the 2026 cycle — driven by 22 new AIIMS campuses and expanded private college capacity. Govt. MBBS seats require 560–655+ marks (General). Below that, private or abroad pathways are more realistic. ⚠️ Beware of agents promising guaranteed seats at very low prices.', 'items' => []],
                ]]),
                // Path 3 — BDS
                $this->sec('p3-head', 'heading', ['surface' => 'card', 'label' => '🦷 Path 3 — BDS (Bachelor of Dental Surgery)', 'heading' => '', 'sub' => '']),
                $this->sec('p3-split', 'split', ['label' => '', 'cards' => [
                    ['heading' => 'Closest alternative to MBBS', 'tone' => '', 'body' => 'BDS is the closest alternative to MBBS. Graduates earn the Dr. prefix, can open private clinics, and established urban practitioners earn ₹25 LPA or more.', 'items' => [
                        ['text' => '🕒 <strong>Duration:</strong> 5 years (4 + 1 yr internship)'],
                        ['text' => '📝 <strong>Admission:</strong> NEET score · MCC + State counselling'],
                        ['text' => '💰 <strong>Fees:</strong> ₹5–40L (govt. to private)'],
                        ['text' => '🎓 <strong>After BDS:</strong> MDS specialisation, private practice, corporate dentistry, academics'],
                    ]],
                    ['heading' => '2026 reality check', 'tone' => 'good', 'body' => 'BDS cutoffs are significantly lower than MBBS — accessible with a NEET score of 300–450 for government seats. India has 313 dental colleges with ~27,000 seats. The dental industry is growing 15% YoY. ODA tip: BDS abroad (Russia, Georgia, Philippines) is also an option at lower cost, with WHO/NMC recognition.', 'items' => []],
                ]]),
                // Path 4 — AYUSH
                $this->sec('p4-head', 'heading', ['surface' => 'card', 'label' => '🌿 Path 4 — AYUSH Courses (BAMS, BHMS, BUMS, BSMS)', 'heading' => '', 'sub' => 'The National AYUSH Mission, a growing wellness industry, and global demand for natural medicine make BAMS one of the most in-demand degrees in 2026. Government medical officers start at ₹50,000–60,000 per month.']),
                $this->sec('p4-table', 'table', [
                    'label' => '',
                    'headings' => $this->li(['Course', 'Full Form', 'Duration', 'Career Outlook']),
                    'rows' => [
                        ['cells' => [['text' => 'BAMS', 'tone' => 'key'], ['text' => 'Ayurvedic Medicine & Surgery', 'tone' => ''], ['text' => '5.5 yrs', 'tone' => ''], ['text' => 'Strongest AYUSH option · Govt. jobs · Private practice', 'tone' => '']]],
                        ['cells' => [['text' => 'BHMS', 'tone' => 'key'], ['text' => 'Homeopathic Medicine & Surgery', 'tone' => ''], ['text' => '5.5 yrs', 'tone' => ''], ['text' => 'Growing demand · Wellness sector · Export potential', 'tone' => '']]],
                        ['cells' => [['text' => 'BUMS', 'tone' => 'key'], ['text' => 'Unani Medicine & Surgery', 'tone' => ''], ['text' => '5.5 yrs', 'tone' => ''], ['text' => 'Middle East demand · Govt. hospitals', 'tone' => '']]],
                        ['cells' => [['text' => 'BSMS', 'tone' => 'key'], ['text' => 'Siddha Medicine & Surgery', 'tone' => ''], ['text' => '5.5 yrs', 'tone' => ''], ['text' => 'Tamil Nadu-specific · Govt. jobs available', 'tone' => '']]],
                    ],
                    'note' => 'All AYUSH courses require a NEET qualifying score and are admitted through MCC/State counselling.',
                ]),
                // Path 5 — Nursing & Allied
                $this->sec('p5-head', 'heading', ['surface' => 'card', 'label' => '🩺 Path 5 — Nursing & Allied Health Sciences', 'heading' => '', 'sub' => 'Government hospital roles offer stability; international placements offer exceptional salaries. Best for students who want job security, global opportunities, and direct patient care.']),
                $this->sec('p5-split', 'split', ['label' => '', 'cards' => [
                    ['heading' => 'Nursing options', 'tone' => '', 'body' => '', 'items' => [
                        ['text' => '<strong>BSc Nursing</strong> — 4 years · NEET accepted · ₹3–10L · global demand'],
                        ['text' => '<strong>GNM</strong> — 3.5 years · diploma level · fastest route to employment'],
                        ['text' => '<strong>Post Basic BSc Nursing</strong> — for GNM graduates wanting a degree upgrade'],
                    ]],
                    ['heading' => 'Allied health sciences', 'tone' => '', 'body' => '', 'items' => [
                        ['text' => '<strong>BPT</strong> (Physiotherapy) — 4.5 yrs · global demand · ₹4–12 LPA'],
                        ['text' => '<strong>BMLT</strong> (Lab Technology) — 3 yrs · hospital backbone role'],
                        ['text' => '<strong>BSc Radiology</strong> — 3 yrs · high demand in private hospitals'],
                        ['text' => '<strong>BSc Optometry</strong> — 4 yrs · growing eyecare sector'],
                    ]],
                ]]),
                $this->sec('p5-global', 'callout', ['accent' => '#185FA5', 'icon' => 'globe', 'label' => 'Global opportunity', 'body' => 'Canada, the UK, Australia, and Gulf countries actively recruit Indian nurses and physiotherapists. BSc Nursing graduates are among the most in-demand healthcare workers globally in 2026.']),
                // Path 6 — Pharmacy
                $this->sec('p6-head', 'heading', ['surface' => 'card', 'label' => '💊 Path 6 — Pharmacy (B.Pharm / Pharm.D)', 'heading' => '', 'sub' => '']),
                $this->sec('p6-split', 'split', ['label' => '', 'cards' => [
                    ['heading' => 'B.Pharm', 'tone' => '', 'body' => 'Duration: 4 years · NEET not mandatory in most states · Fees: ₹40,000–2L/yr. Roles: Clinical Pharmacist, Drug Inspector, R&D, Pharma Marketing. Considered the highest-paying healthcare job without NEET — graduates can earn ₹6–12 LPA.', 'items' => []],
                    ['heading' => 'Pharm.D', 'tone' => '', 'body' => 'Duration: 6 years · clinical focus · hospital and community pharmacy. Equivalent to doctorate level in pharmacy practice. Strong demand in the US, UK, and Gulf for Indian Pharm.D graduates with licensing exams.', 'items' => []],
                ]]),
                // Decision guide
                $this->sec('decision', 'table', [
                    'label' => '🎯 Which Path Is Right For You? — Quick Decision Guide',
                    'headings' => $this->li(['Your Situation', 'Best Pathway']),
                    'rows' => [
                        ['cells' => [['text' => 'High NEET score (560+), General category', 'tone' => ''], ['text' => 'Govt. MBBS India — your first choice', 'tone' => 'good']]],
                        ['cells' => [['text' => 'NEET 300–550, family can invest ₹60–120L', 'tone' => ''], ['text' => 'Private MBBS India or MBBS Abroad (compare costs)', 'tone' => 'good']]],
                        ['cells' => [['text' => 'NEET qualifying score, budget ₹20–40L', 'tone' => ''], ['text' => 'MBBS Abroad — Russia, Georgia, or Uzbekistan', 'tone' => 'good']]],
                        ['cells' => [['text' => 'NEET 300–450, want to practice as a doctor in India', 'tone' => ''], ['text' => 'BDS (Dental) or BAMS — respected, rewarding careers', 'tone' => 'good']]],
                        ['cells' => [['text' => 'Any NEET score, want global jobs quickly', 'tone' => ''], ['text' => 'BSc Nursing or BPT — highest global demand in 2026', 'tone' => 'good']]],
                        ['cells' => [['text' => 'NEET not required, interested in science + business', 'tone' => ''], ['text' => 'B.Pharm / Pharm.D — high salary, no NEET needed', 'tone' => 'good']]],
                    ],
                ]),
                $this->sec('dates', 'timeline', [
                    'surface' => 'card',
                    'label' => '📅 Key Dates — June–September 2026',
                    'rows' => [
                        ['date' => 'Jun 14', 'detail' => 'Re-NEET 2026 admit cards released at neet.nta.nic.in'],
                        ['date' => 'Jun 21', 'detail' => 'Re-NEET UG 2026 exam (2:00 PM – 5:15 PM, offline)'],
                        ['date' => 'Jun 28', 'detail' => 'FMGE June 2026 exam — 300 MCQs, two shifts'],
                        ['date' => 'Jul–Aug', 'detail' => 'NEET results · MCC AIQ counselling Round 1 opens · State counselling begins'],
                        ['date' => 'Aug–Sep', 'detail' => 'MBBS abroad Sept 2026 intake deadlines — Russia, Georgia, Uzbekistan'],
                        ['date' => 'Aug–Sep', 'detail' => 'Scholarship windows close — NSP, Turkish Govt., Chinese Govt. (CSC) scholarships'],
                    ],
                ]),
                $this->sec('cta', 'cta_band', [
                    'heading' => 'Not sure which path fits your score and budget?',
                    'body' => "Book a free personalised counselling session with ODA. We'll map your NEET score, budget, and career goals to the right pathway — honestly, without pressure.",
                    'btn_label' => 'Book free counselling', 'btn_icon' => 'calendar-check', 'btn_href' => '/contact',
                ]),
            ],
        ];
    }

    /* ───────────────────────── Wednesday Briefings ───────────────────────── */
    private function wednesday(): array
    {
        return [
            'slug' => 'wednesday-briefings',
            'path' => '/wednesday-briefings',
            'title' => 'Wednesday Briefings',
            'page_title' => 'Wednesday Briefings — Study Abroad Flash | One Degree Advisory',
            'meta_description' => 'This week in study abroad: three world-class universities approved to open India campuses, and tightening policies across the UK, US, Canada, and Australia.',
            'visible' => true,
            'sections' => [
                $this->sec('hero', 'hero', [
                    'eyebrow' => 'Study Abroad Flash · Wednesday',
                    'title' => 'Study Abroad Flash.',
                    'copy' => 'The global study abroad landscape changed significantly this week. Three world-class universities just received approval to open campuses in India, while all major overseas destinations — UK, US, Canada, Australia — are tightening policies. Here is what it means for your decisions right now.',
                    'actions' => [
                        ['label' => 'Plan with an advisor', 'icon' => 'compass', 'href' => '/contact', 'style' => 'primary'],
                    ],
                    'panel_heading' => 'What changed this week.',
                    'panel_items' => [
                        ['icon' => 'building-2', 'text' => 'Bristol, York & UNSW approved for India campuses.'],
                        ['icon' => 'flag', 'text' => 'UK, Australia, Canada tighten student policies.'],
                        ['icon' => 'scale', 'text' => 'New choice: a global degree in India vs abroad.'],
                    ],
                ]),
                $this->sec('breaking-head', 'heading', ['surface' => 'card', 'label' => '🔴 Breaking Today', 'heading' => 'Bristol, York & UNSW approved to open India campuses — August 2026', 'sub' => 'The Ministry of Education has issued Letters of Approval to three globally ranked universities to establish campuses in India under NEP 2020. UNSW Bengaluru opens at Manyata Business Park, Bengaluru in August 2026; the University of York and University of Bristol open in Mumbai. These are full degree-granting campuses — the same academic standards as the parent university overseas, at 40–60% lower fees. Already operational: University of Southampton (Gurugram, 2025), Deakin University (GIFT City, 2024). 18 foreign universities total are launching in India by August 2026.']),
                $this->sec('campuses', 'card_grid', ['label' => '', 'cards' => [
                    ['emoji' => '🇦🇺', 'title' => 'UNSW Bengaluru', 'meta' => 'Australia · Opens Aug 2026', 'body' => 'Business · CS · Data Science · Cybersecurity'],
                    ['emoji' => '🇬🇧', 'title' => 'University of York', 'meta' => 'UK · Mumbai · 2026', 'body' => 'Computer Science · Cyber Security'],
                    ['emoji' => '🇬🇧', 'title' => 'University of Bristol', 'meta' => 'UK · Mumbai · 2026', 'body' => 'Programs TBC · Top 10 UK University'],
                ]]),
                $this->sec('updates', 'brief_cards', ['label' => "🌐 Destination Updates — What's Changed", 'cards' => [
                    ['title' => '🇬🇧 UK — Higher visa fees; dependent restrictions; 97% acceptance still holds', 'level' => 'high', 'body' => "The UK raised immigration fees from April 2026. Students on taught postgraduate courses (MSc, MA) can no longer bring dependants — exceptions apply to PhD and research programmes. Despite this, the UK's 1-year Masters, strong rankings, and Graduate visa route remain compelling. Indian students maintain a 97% visa acceptance rate.", 'tags' => []],
                    ['title' => '🇦🇺 Australia — Private college crackdown; public universities remain strong', 'level' => 'medium', 'body' => 'Australia has blocked private colleges from offering new courses as part of a student visa crackdown, and Ministerial Direction 115 adds scrutiny to all applications. However, Group of Eight (Go8) and public universities remain strong pathways. The MATES ballot for NIRF top-100 graduates is open — apply before January 2027 for the full 2-year Graduate visa.', 'tags' => []],
                    ['title' => '🇨🇦 Canada — 50% intake cap cut; Masters & PhD largely exempted', 'level' => 'medium', 'body' => 'Canada has cut study permit volumes by 50% for undergraduate programs. Postgraduate research streams — Masters and PhD — are largely exempted. Express Entry now prioritises healthcare, science, engineering, and skilled trades through category-based draws. Canada remains viable for postgraduate students with a clear career pathway.', 'tags' => []],
                    ['title' => '🌍 Germany & New Zealand — Rising alternatives worth knowing', 'level' => 'medium', 'body' => 'Germany is seeing strong growth in Indian student enrolment as the Big 4 tighten, with public universities charging near-zero tuition. New Zealand raised student work rights to 25 hours/week from June 2026 and simplified degree recognition — positioning itself as a more accessible alternative to Australia.', 'tags' => []],
                ]]),
                $this->sec('ibc', 'table', [
                    'label' => '⚖️ New Option: Global Degree in India vs. Study Abroad',
                    'headings' => $this->li(['Factor', 'India Campus (IBC)', 'Study Abroad']),
                    'rows' => [
                        ['cells' => [['text' => 'Total Cost', 'tone' => 'key'], ['text' => '40–60% lower', 'tone' => 'good'], ['text' => 'Full overseas cost + living', 'tone' => '']]],
                        ['cells' => [['text' => 'Degree', 'tone' => 'key'], ['text' => 'Same degree as parent campus', 'tone' => 'good'], ['text' => 'Same degree as parent campus', 'tone' => '']]],
                        ['cells' => [['text' => 'Visa Risk', 'tone' => 'key'], ['text' => 'None', 'tone' => 'good'], ['text' => 'Increasing in all Big 4', 'tone' => 'warn']]],
                        ['cells' => [['text' => 'International Exposure', 'tone' => 'key'], ['text' => 'Limited — you stay in India', 'tone' => 'warn'], ['text' => 'Full immersive experience', 'tone' => 'good']]],
                        ['cells' => [['text' => 'PR / Work Abroad', 'tone' => 'key'], ['text' => 'Harder — no overseas residency', 'tone' => 'warn'], ['text' => 'Graduate visa pathways available', 'tone' => 'good']]],
                    ],
                    'note' => 'ODA can help you evaluate both options based on your career goals, budget, and risk tolerance.',
                ]),
                $this->sec('todo', 'talk', ['label' => '✅ 3 Things to Do Right Now', 'quoted' => false, 'items' => $this->li([
                    'Shortlist destinations based on your career goal — not just rankings. Want to work in India after graduation? An India campus (IBC) or a German university may offer better ROI. Want PR abroad? Australia (Go8), Canada (Masters), or New Zealand offer clearer pathways. Budget the main constraint? Germany, IBC, or Ireland.',
                    'Apply early — September 2026 and January 2027 intakes are now open. September deadlines are approaching for most universities; January 2027 intakes are open for UK and Australian institutions. Early applications get stronger scholarship consideration and avoid last-minute visa stress.',
                    'Talk to an advisor before you commit. Visa rules are shifting across every major destination — a 30-minute profile review now can save months of rework and protect your scholarship and intake options.',
                ])]),
                $this->sec('cta', 'cta_band', [
                    'heading' => 'The right decision now saves years of regret later.',
                    'body' => "Book a free 1-on-1 counselling session with ODA — we'll map your goals, budget, and timeline to the right destination and university.",
                    'btn_label' => 'Book a free session', 'btn_icon' => 'calendar-check', 'btn_href' => '/contact',
                ]),
            ],
        ];
    }
}
