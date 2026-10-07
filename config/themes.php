<?php

/*
 * The looks the journey planner and the student portal can wear.
 *
 * Each theme lists, per surface, the stylesheets that make it up; the
 * partials.theme-head partial writes the chosen theme's sheets before the
 * page paints. The choice is the viewer's own, kept in their browser
 * (localStorage "jpTheme"), and falls back to "default" here. A theme that
 * offers light, dark and automatic appearance says so with "appearance";
 * one whose glass can be tinted or clear says so with "material".
 *
 * To add a theme: add an entry with its sheets. The account menu in the
 * planner lists every theme here, so nothing else has to change.
 *
 * Surfaces: "planner" is the journey planner (the counsellor's at
 * /crm/students/{lead}/planner and the student's at /student); "auth" is the
 * student's sign-in and change-password pages.
 */

return [
    'planner' => [
        'default' => 'glass',

        'themes' => [
            // iOS / macOS 26 Liquid Glass over a pastel wallpaper, the glass
            // built as in the "MacOS 26 – Liquid Glass Effect" Figma file.
            // Light (default) or dark; tinted or clear glass.
            'glass' => [
                'label' => 'Glass',
                'appearance' => true,
                'material' => true,
                'planner' => [
                    'assets/glass/glass-tokens.css',
                    'assets/journey/planner-glass.css',
                ],
                'auth' => [
                    'assets/glass/glass-tokens.css',
                    'assets/student/portal-auth-glass.css',
                ],
            ],

            // The website's cream theme: indigo, orange, Cormorant headings.
            'standard' => [
                'label' => 'Standard',
                'appearance' => false,
                'planner' => [
                    'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap',
                    'assets/journey/planner.css',
                ],
                'auth' => [
                    'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600;1,700&family=Manrope:wght@400;500;600;700;800&display=swap',
                    'assets/student/portal-auth.css',
                ],
            ],
        ],
    ],
];
