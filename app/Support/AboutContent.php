<?php

namespace App\Support;

/**
 * Read interface + seed data for the About page. The editable JSON store is the
 * source of truth once the CMS runs; this seed is written once on first load so
 * the published page keeps its original content until an editor changes it.
 */
class AboutContent
{
    /** Every section in display order (admin sees all; the public page filters by `visible`). */
    public function all(): array
    {
        return app(AboutStore::class)->all();
    }

    /** Only the sections that should render on the public page, in order. */
    public function visible(): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (array $s) => ($s['visible'] ?? true) === true
        ));
    }

    /** The built-in seed — the current About page expressed as editable sections. */
    public function defaults(): array
    {
        return [
            [
                'id' => 'hero',
                'type' => 'hero',
                'visible' => true,
                'data' => [
                    'eyebrow' => 'About One Degree',
                    'heading_pre' => 'Join ',
                    'heading_highlight' => '12,000+ students',
                    'heading_mid' => ' architecting their ',
                    'heading_em' => 'global careers.',
                    'lede' => 'We are a senior, partner-led advisory built on a simple promise — the person who designs your shortlist is the same one reading your final draft and rehearsing your visa interview. No handoffs. No volume targets. Just one careful method, every file.',
                    'actions' => [
                        ['label' => 'Talk to a partner', 'href' => '/contact', 'icon' => 'arrow-up-right', 'style' => 'primary'],
                        ['label' => 'Meet the team', 'href' => '#founders', 'icon' => 'users', 'style' => 'ghost'],
                    ],
                    'metrics' => [
                        ['value' => '20+', 'label' => 'Study destinations'],
                        ['value' => '96%', 'label' => 'Visa approvals'],
                        ['value' => '01', 'label' => 'Senior per file'],
                    ],
                    'photo_lg' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=900&h=1100&q=82',
                    'photo_sm' => 'https://images.unsplash.com/photo-1521737852567-6949f3f9f2b5?auto=format&fit=crop&w=600&h=600&q=82',
                    'badge_icon' => 'sparkles',
                    'badge_title' => 'Partner-led',
                    'badge_subtitle' => 'No junior account managers',
                ],
            ],
            [
                'id' => 'vision-mission',
                'type' => 'cards',
                'visible' => true,
                'data' => [
                    'eyebrow' => 'Vision & Mission',
                    'heading' => 'Two ideas anchor everything we do.',
                    'cards' => [
                        [
                            'accent' => 'vision',
                            'icon' => 'telescope',
                            'tag' => 'Our Vision',
                            'heading' => 'To be the most trusted education partner — helping every student unlock their full potential through the right opportunities.',
                            'body' => 'A future where the next generation chooses programs by evidence, not by ad spend; and where every family gets the same careful read top universities give their own admits.',
                        ],
                        [
                            'accent' => 'mission',
                            'icon' => 'compass',
                            'tag' => 'Our Mission',
                            'heading' => 'To champion student success — guiding them toward academic and career goals so every step leads to real achievement.',
                            'body' => 'We turn scattered research into a decision map, defend the file from bad advice, and stay in the room from profile build to pre-departure.',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'pillars',
                'type' => 'pillars',
                'visible' => true,
                'data' => [
                    'items' => [
                        [
                            'anchor' => 'who',
                            'reverse' => false,
                            'eyebrow' => 'Who We Are',
                            'heading' => 'A small bench of senior advisors — not a referral machine.',
                            'body' => 'At One Degree Advisory, we are a dedicated team of education experts who believe your future deserves more than promises — it deserves the best read, the best draft, and the best plan. Our partners have sat inside admissions offices, consular desks, and test-prep rooms. They know how files actually get read.',
                            'image' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&h=720&q=82',
                            'image_alt' => 'One Degree advisors working with a student',
                            'tag_icon' => 'users-round',
                            'tag_label' => 'The team',
                            'chips' => [
                                ['icon' => 'badge-check', 'label' => 'Dream Enablers'],
                                ['icon' => 'graduation-cap', 'label' => 'Education Experts'],
                                ['icon' => 'shield-check', 'label' => 'Partner-only Reviews'],
                            ],
                        ],
                        [
                            'anchor' => 'why',
                            'reverse' => true,
                            'eyebrow' => 'Why We Do It',
                            'heading' => 'Because academic journeys feel overwhelming — and most advice is built to sell, not to fit.',
                            'body' => 'Countless options. Unexpected costs. Advice that answers to commissions, not to you. We started One Degree because the conversation around studying abroad had grown loud, transactional, and quietly unfair to families. We wanted a desk where the advice is independent — and accountable.',
                            'image' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=900&h=720&q=82',
                            'image_alt' => 'Student reviewing study-abroad plans',
                            'tag_icon' => 'heart-handshake',
                            'tag_label' => 'Why we exist',
                            'chips' => [
                                ['icon' => 'trophy', 'label' => 'Student Victory'],
                                ['icon' => 'target', 'label' => 'Goal & Dream Alignment'],
                                ['icon' => 'hand-coins', 'label' => 'Zero Commissions'],
                            ],
                        ],
                        [
                            'anchor' => 'what',
                            'reverse' => false,
                            'eyebrow' => 'What We Do',
                            'heading' => 'One method. Profile to pre-departure.',
                            'body' => 'One Degree is your end-to-end partner for further studies. We connect you to the right programs and stay with you through every milestone — profile build, shortlist, applications, scholarships, tests, visas, and the first month abroad. One file. One partner. One careful plan.',
                            'image' => 'https://images.unsplash.com/photo-1607013251379-e6eecfffe234?auto=format&fit=crop&w=900&h=720&q=82',
                            'image_alt' => 'Advisor and student mapping a university shortlist',
                            'tag_icon' => 'layers',
                            'tag_label' => 'What we deliver',
                            'chips' => [
                                ['icon' => 'sparkles', 'label' => 'Evidence-led Match'],
                                ['icon' => 'route', 'label' => 'End-to-end Support'],
                                ['icon' => 'plane-takeoff', 'label' => 'Pre-departure Lab'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'impact',
                'type' => 'impact',
                'visible' => true,
                'data' => [
                    'eyebrow' => 'Our Global Impact',
                    'heading' => 'Quietly, the numbers add up.',
                    'intro' => 'At One Degree, we have helped thousands of students earn seats at the world’s most selective programs. Our track record is built file by file — not by chasing volume.',
                    'stats' => [
                        ['icon' => 'globe-2', 'value' => '20', 'suffix' => '+', 'label' => 'Countries accessible for study-abroad opportunities'],
                        ['icon' => 'users', 'value' => '12,000', 'suffix' => '+', 'label' => 'Students guided on their global education journey'],
                        ['icon' => 'building-2', 'value' => '9,400', 'suffix' => '+', 'label' => 'Students successfully placed in top institutions'],
                        ['icon' => 'award', 'value' => '34', 'suffix' => '%', 'label' => 'Higher acceptance rate than the industry average'],
                    ],
                ],
            ],
            [
                'id' => 'founders',
                'type' => 'team',
                'visible' => true,
                'data' => [
                    'anchor' => 'founders',
                    'eyebrow' => 'Founders',
                    'heading' => 'The partners who actually read your file.',
                    'intro' => 'Senior advisors with two decades each inside admissions, consular work, and test strategy — not a sales floor.',
                    'members' => [
                        [
                            'photo' => 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?auto=format&fit=crop&w=600&h=720&q=82',
                            'name' => 'Aanya Mehra',
                            'role' => 'Founder · Managing Partner',
                            'bio' => 'Edtech leader and former admissions reader at a top-ten US university. TEDx speaker; mentors first-generation applicants worldwide. Leads strategy for selective undergraduate and MBA files.',
                            'desk' => 'US · Canada',
                            'linkedin' => '#',
                        ],
                        [
                            'photo' => 'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=600&h=720&q=82',
                            'name' => 'Rohan Iyer',
                            'role' => 'Founder · Partner',
                            'bio' => 'Serial entrepreneur with 25+ years in global education. Oxford alum and 15-year admissions interviewer. Runs the Europe desk and the in-house scholarship lab.',
                            'desk' => 'UK · Europe',
                            'linkedin' => '#',
                        ],
                        [
                            'photo' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=600&h=720&q=82',
                            'name' => 'Navyata Goenka',
                            'role' => 'Founder · Partner',
                            'bio' => 'Investment banking and equity research background. Kelley School of Business alumna. Advisor to Mount Litera School International (4 years); recognised among “Young Women Entrepreneurs Leading a New India.”',
                            'desk' => 'Profile · Finance',
                            'linkedin' => '#',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'cta',
                'type' => 'cta',
                'visible' => true,
                'data' => [
                    'eyebrow' => 'Ready to turn your dream into reality?',
                    'heading' => 'Bring us the messy draft. We will turn it into a decision map.',
                    'body' => 'One call with a senior partner. No sales pitch, no commissions, no junior account managers between you and the people making decisions on your file.',
                    'actions' => [
                        ['label' => 'Get in touch', 'href' => '/contact', 'icon' => 'arrow-up-right', 'style' => 'primary'],
                        ['label' => 'Meet a partner first', 'href' => '#founders', 'icon' => 'user-check', 'style' => 'ghost'],
                    ],
                    'tags' => [
                        ['icon' => 'sparkle', 'label' => 'AI-driven evaluation'],
                        ['icon' => 'life-buoy', 'label' => 'Comprehensive support'],
                        ['icon' => 'lock', 'label' => 'Confidential review'],
                    ],
                    'image' => 'https://images.unsplash.com/photo-1523580494863-6f3031224c94?auto=format&fit=crop&w=900&h=1100&q=82',
                    'image_alt' => '',
                    'stat_num' => '96%',
                    'stat_label' => 'Top-choice visa approvals last cycle',
                ],
            ],
        ];
    }
}
