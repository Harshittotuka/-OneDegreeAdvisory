<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guards for the CRM's theme system.
 *
 * Each theme is a COMPLETE, standalone stylesheet — the loader in
 * crm/dashboard.blade.php swaps one file for another, it does not layer them.
 * A rule that exists in only one theme is therefore an unstyled component in
 * the others, which is how Team management ended up with white cards and purple
 * badges on evergreen's cream.
 *
 * The contract and the keyframe check run against all three themes; the
 * completeness and frame-width assertions are specific to Classic, which was
 * rewritten from scratch as the plain, professional, wide-frame option.
 */
class CrmThemeTest extends TestCase
{
    private const EVERGREEN = 'assets/crm/crm.css';

    private const CLASSIC = 'assets/crm/crm-classic.css';

    private const ORBIT = 'assets/crm/crm-orbit.css';

    /**
     * Things every theme has to style because the app renders the markup and
     * something breaks or misleads without them.
     *
     * Deliberately NOT a diff against another theme: Orbit legitimately styles
     * the same components through different selectors (its horizontal nav is
     * .sidebar>.nav, its activity stream hangs off .activity-item), so
     * selector-for-selector parity flags correct code. This list is the
     * functional floor instead — each entry was a real defect when it was
     * missing.
     *
     * @var array<string, string>
     */
    private const CONTRACT = [
        '.field.has-error' => 'a rejected form shows no invalid field',
        '.field-error:before' => 'the validation message loses its marker',
        '.btn.is-busy' => 'a submit in flight shows no spinner',
        // Must be the direct-child form: a bare ".stat span" also matches
        // .stat-top and .stat-icon and, at higher specificity than .stat-icon,
        // strips the icon's centring and shrinks its glyph.
        '.stat>span:not(.stat-top)' => 'the KPI label renders at full body size, or the label rule eats the icon',
        '.badge:before' => 'the status dot the badge reserves a gap for is missing',
        '.pipeline-step:first-child:before' => 'the first pipeline step draws a connector to nothing',
        '.drawer-tabs.is-stuck' => 'crm.js sets .is-stuck on scroll and nothing responds',
        '.journey-step.complete' => 'a completed journey stage looks unvisited',
        '.next-action-card.today' => 'a follow-up due today looks like any other',
        '.next-action-card.upcoming' => 'an upcoming follow-up looks like any other',
        '.modal>form' => 'a long modal body cannot scroll and pushes its footer away',
        '[data-crm-href]' => 'clickable table rows give no pointer cursor',
        ':focus-visible' => 'keyboard focus is invisible',
    ];

    public function test_classic_styles_every_component_evergreen_does(): void
    {
        $missing = array_values(array_diff(
            $this->selectors(self::EVERGREEN),
            $this->selectors(self::CLASSIC),
        ));
        sort($missing);

        $this->assertSame([], $missing, sprintf(
            "crm-classic.css does not style %d selector(s) that crm.css does, so those components fall back to browser defaults on the Classic theme:\n  %s",
            count($missing),
            implode("\n  ", array_slice($missing, 0, 40)),
        ));
    }

    public function test_every_theme_meets_the_functional_contract(): void
    {
        foreach ([self::EVERGREEN, self::CLASSIC, self::ORBIT] as $theme) {
            $css = $this->css($theme);

            foreach (self::CONTRACT as $selector => $consequence) {
                $this->assertStringContainsString(
                    $selector,
                    $css,
                    basename($theme).' never styles '.$selector.' — '.$consequence.'.',
                );
            }
        }
    }

    /**
     * A theme may use whatever animation vocabulary it likes — Orbit runs its
     * own orbit-* set — but every name it references has to resolve, or the
     * element sits at its animation's starting state forever.
     */
    public function test_no_theme_references_an_animation_it_never_defines(): void
    {
        foreach ([self::EVERGREEN, self::CLASSIC, self::ORBIT] as $theme) {
            $css = $this->css($theme);

            preg_match_all('/@keyframes\s+([\w-]+)/', $css, $defined);
            preg_match_all('/animation(?:-name)?\s*:\s*([^;}]+)/', $css, $used);

            $referenced = [];
            foreach ($used[1] as $value) {
                foreach (preg_split('/[,\s]+/', $value) as $token) {
                    if (preg_match('/^(crm|orbit)-[\w-]+$/', $token)) {
                        $referenced[$token] = true;
                    }
                }
            }

            $undefined = array_values(array_diff(array_keys($referenced), $defined[1]));
            sort($undefined);

            $this->assertSame([], $undefined, basename($theme).' references keyframes it never defines: '.implode(', ', $undefined));
        }
    }

    /**
     * crm-dashboard.css is loaded alongside every theme and reads a handful of
     * theme-owned tokens. They all carry fallbacks, but a theme that leaves them
     * undeclared inherits another theme's colours through those fallbacks —
     * which is why Classic declares --pine / --pine-soft as accent aliases.
     */
    public function test_classic_declares_the_tokens_the_shared_stylesheet_reads(): void
    {
        $css = $this->css(self::CLASSIC);

        // Exactly the tokens the shared stylesheets read without declaring
        // themselves. --radius and --shadow used to be on this list and are
        // not read by anything outside a theme, so asserting them proved
        // nothing; --soft-2 and --topbar-h are read and were missing from it.
        foreach (['--card', '--line', '--muted', '--soft', '--soft-2', '--navy', '--font-display', '--pine', '--pine-soft', '--topbar-h'] as $token) {
            $this->assertMatchesRegularExpression(
                '/'.preg_quote($token, '/').'\s*:/',
                $css,
                $token.' is read by crm-dashboard.css but Classic never declares it.',
            );
        }
    }

    /**
     * The point of the rewrite: Classic shows more at once than the theme it
     * sits beside. Each frame is compared against evergreen rather than pinned
     * to a number, so the intent survives future tuning of either theme.
     */
    public function test_classic_uses_wider_frames_than_evergreen(): void
    {
        $classic = $this->css(self::CLASSIC);
        $evergreen = $this->css(self::EVERGREEN);

        $frames = [
            'content column' => '/\.content\{[^}]*max-width:(\d+)px/',
            'sidebar rail' => '/\.shell\{[^}]*grid-template-columns:(\d+)px/',
            'lead drawer' => '/\.drawer\{[^}]*width:min\((\d+)px/',
            'expanded drawer' => '/\.drawer-overlay\.is-expanded \.drawer-body\{[^}]*max-width:(\d+)px/',
        ];

        foreach ($frames as $label => $pattern) {
            $this->assertSame(1, preg_match($pattern, $classic, $c), "Could not read the {$label} width from Classic.");
            $this->assertSame(1, preg_match($pattern, $evergreen, $e), "Could not read the {$label} width from evergreen.");

            if ($label === 'sidebar rail') {
                // The one that gets SMALLER — a narrower rail is what hands the
                // extra width to the content beside it.
                $this->assertLessThan((int) $e[1], (int) $c[1], 'The Classic sidebar should be narrower than evergreen’s.');

                continue;
            }

            $this->assertGreaterThan((int) $e[1], (int) $c[1], "The Classic {$label} should be wider than evergreen’s.");
        }
    }

    public function test_all_three_themes_are_offered_and_loadable(): void
    {
        foreach (['crm.css', 'crm-classic.css', 'crm-orbit.css'] as $file) {
            $this->assertFileExists(public_path('assets/crm/'.$file));
        }

        $this->get(route('crm.login'))
            ->assertOk()
            ->assertSee('data-classic-href=', false)
            ->assertSee('data-evergreen-href=', false)
            ->assertSee('data-orbit-href=', false)
            ->assertSee("['classic', 'evergreen', 'orbit'].includes(savedTheme)", false);
    }

    private function css(string $path): string
    {
        $css = file_get_contents(public_path($path));
        $this->assertNotFalse($css, $path.' could not be read.');

        return $css;
    }

    /**
     * Every selector a stylesheet defines a rule for, comments stripped and
     * comma-separated groups split apart.
     *
     * @return list<string>
     */
    private function selectors(string $path): array
    {
        $css = preg_replace('/\/\*.*?\*\//s', '', $this->css($path));
        preg_match_all('/(?:^|\}|\{)\s*([^{}@][^{}]*?)\s*\{/', $css, $matches);

        $selectors = [];
        foreach ($matches[1] as $group) {
            foreach (explode(',', $group) as $selector) {
                $selector = trim($selector);
                if ($selector !== '') {
                    $selectors[$selector] = true;
                }
            }
        }

        return array_keys($selectors);
    }
}
