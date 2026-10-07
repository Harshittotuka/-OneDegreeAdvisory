<?php

/*
 * The looks the journey planner and the student portal can wear.
 *
 * Each theme lists, per surface, the stylesheets that make it up; the
 * partials.theme-head partial writes the chosen theme's sheets before the
 * page paints. The choice is the viewer's own, kept in their browser
 * (localStorage "jpTheme"), and falls back to "default" here: OneDegree,
 * the website's own look. The menu lists the themes in the order below. A theme that
 * offers light, dark and automatic appearance says so with "appearance";
 * one whose glass can be tinted or clear says so with "material"; one with
 * a choice of backgrounds lists them under "wallpapers".
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
        'default' => 'standard',

        'themes' => [
            // The website's cream theme: indigo, orange, Cormorant headings.
            'standard' => [
                'label' => 'OneDegree',
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

            // iOS / macOS 26 Liquid Glass over a pastel wallpaper, the glass
            // built as in the "MacOS 26 – Liquid Glass Effect" Figma file.
            // Light (default) or dark; tinted or clear glass.
            'glass' => [
                'label' => 'Glass',
                'appearance' => true,
                'material' => true,
                // Backgrounds the viewer can put behind the glass. "pastel" is the
                // built-in gradient (glass-tokens.css); the rest are photos from
                // Unsplash (free for commercial use, no attribution needed, see
                // unsplash.com/license), downloaded and served from
                // assets/glass/wallpapers, with a 320x200 thumbnail in thumbs/.
                // "tone" is how light the top of the photo is, where the page
                // title sits on it: text there turns white on a dark photo.
                'wallpaper' => 'pastel',
                'wallpapers' => [
                    ['key' => 'pastel', 'label' => 'Pastel', 'group' => 'Colours', 'tone' => 'light'],
                    ['key' => 'waves', 'label' => 'Waves', 'group' => 'Abstract', 'tone' => 'dark', 'source' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe'],
                    ['key' => 'prism', 'label' => 'Prism', 'group' => 'Abstract', 'tone' => 'light', 'source' => 'https://images.unsplash.com/photo-1579546929518-9e396f3cc809'],
                    ['key' => 'silk', 'label' => 'Silk', 'group' => 'Abstract', 'tone' => 'light', 'source' => 'https://images.unsplash.com/photo-1604076913837-52ab5629fba9'],
                    ['key' => 'indigo', 'label' => 'Indigo', 'group' => 'Abstract', 'tone' => 'dark', 'source' => 'https://images.unsplash.com/photo-1557682250-33bd709cbe85'],
                    ['key' => 'aurora', 'label' => 'Aurora', 'group' => 'Abstract', 'tone' => 'dark', 'source' => 'https://images.unsplash.com/photo-1620641788421-7a1c342ea42e'],
                    ['key' => 'violet', 'label' => 'Violet', 'group' => 'Abstract', 'tone' => 'dark', 'source' => 'https://images.unsplash.com/photo-1635776062127-d379bfcba9f8'],
                    ['key' => 'neon-dusk', 'label' => 'Neon dusk', 'group' => 'Abstract', 'tone' => 'dark', 'source' => 'https://images.unsplash.com/photo-1614850523459-c2f4c699c52e'],
                    ['key' => 'above-the-clouds', 'label' => 'Above the clouds', 'group' => 'Landscapes', 'tone' => 'light', 'source' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4'],
                    ['key' => 'lone-tree', 'label' => 'Lone tree', 'group' => 'Landscapes', 'tone' => 'dark', 'source' => 'https://images.unsplash.com/photo-1494500764479-0c8f2919a3d8'],
                    ['key' => 'stillwater', 'label' => 'Stillwater', 'group' => 'Landscapes', 'tone' => 'dark', 'source' => 'https://images.unsplash.com/photo-1511300636408-a63a89df3482'],
                    ['key' => 'dolomites', 'label' => 'Dolomites', 'group' => 'Landscapes', 'tone' => 'light', 'source' => 'https://images.unsplash.com/photo-1508739773434-c26b3d09e071'],
                    ['key' => 'alpine-lake', 'label' => 'Alpine lake', 'group' => 'Landscapes', 'tone' => 'light', 'source' => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470'],
                    ['key' => 'shoreline', 'label' => 'Shoreline', 'group' => 'Landscapes', 'tone' => 'light', 'source' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e'],
                    ['key' => 'misty-forest', 'label' => 'Misty forest', 'group' => 'Landscapes', 'tone' => 'light', 'source' => 'https://images.unsplash.com/photo-1418065460487-3e41a6c84dc5'],
                    ['key' => 'desert-road', 'label' => 'Desert road', 'group' => 'Landscapes', 'tone' => 'light', 'source' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee'],
                    ['key' => 'coastline', 'label' => 'Coastline', 'group' => 'Landscapes', 'tone' => 'light', 'source' => 'https://images.unsplash.com/photo-1475924156734-496f6cac6ec1'],
                    ['key' => 'night-peaks', 'label' => 'Night peaks', 'group' => 'Night', 'tone' => 'dark', 'source' => 'https://images.unsplash.com/photo-1519681393784-d120267933ba'],
                    ['key' => 'starfield', 'label' => 'Starfield', 'group' => 'Night', 'tone' => 'dark', 'source' => 'https://images.unsplash.com/photo-1419242902214-272b3f66ee7a'],
                ],
                'planner' => [
                    'assets/glass/glass-tokens.css',
                    'assets/journey/planner-glass.css',
                ],
                'auth' => [
                    'assets/glass/glass-tokens.css',
                    'assets/student/portal-auth-glass.css',
                ],
            ],
        ],
    ],
];
