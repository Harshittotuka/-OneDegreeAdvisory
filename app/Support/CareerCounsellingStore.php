<?php

namespace App\Support;

/**
 * File-backed store for the Career Counselling page's "Plans & Pricing"
 * section (/career-counselling).
 *
 * Everything the CMS tab manages lives in one editable JSON file so there is no
 * database dependency: the section heading, the school-stage tabs, the plan
 * cards (name, subtitle, badge, feature list) and — the point of the exercise —
 * every price, expressed as one or more "session tiers" per plan.
 *
 * A tier row is the single source of truth for BOTH the price printed on the
 * card and the amount charged at checkout: the browser only ever sends the
 * flat option index it rendered, and the amount is re-derived server-side from
 * this file at order time (see PaymentBlockResolver::resolveCareerCounselling).
 */
class CareerCounsellingStore
{
    /**
     * Sentinel "page slug" under which counselling payments are recorded in the
     * payment_attempts table and resolved by PaymentBlockResolver. Mirrors the
     * brief-page slug shape (^[a-z0-9-]+$) so PaymentController validation passes.
     */
    public const PAGE_SLUG = 'career-counselling';

    /** The one payment block on this page. */
    public const BLOCK_ID = 'plans';

    /** Bounds — a stage tab strip stops being a tab strip past a handful. */
    public const MAX_STAGES = 4;
    public const MAX_TIERS = 4;
    public const MAX_FEATURES = 10;

    private string $path;

    /** Memo so repeated get() calls on one instance read disk once. */
    private ?array $cache = null;

    public function __construct()
    {
        $this->path = storage_path('app/career-counselling.json');
    }

    /** The full config, with defaults filled in for any missing keys. */
    public function get(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (! is_file($this->path)) {
            $seed = $this->normalize($this->defaults());
            $this->write($seed);

            return $this->cache = $seed;
        }

        $data = json_decode((string) file_get_contents($this->path), true);

        return $this->cache = $this->normalize(is_array($data) ? $data : []);
    }

    public function save(array $data): array
    {
        $clean = $this->normalize($data);
        $this->write($clean);
        $this->cache = $clean;

        return $clean;
    }

    /** Stage labels, in stored order. A plan's "stage" is an index into this. */
    public function stages(): array
    {
        return $this->get()['stages'];
    }

    /** Only the plans that should appear on the public site, in stored order. */
    public function visiblePlans(): array
    {
        return array_values(array_filter(
            $this->get()['plans'],
            fn (array $p) => $p['visible'] ?? true
        ));
    }

    /**
     * Every payable (plan, tier) pair, flattened in render order — this list IS
     * the option_index space the browser sends back. Each entry carries enough
     * context to name the order line without a second lookup.
     *
     * A tier priced at 0 ("fee on request") is deliberately absent: it is shown
     * on the card but cannot be paid online, so it must never occupy an index.
     */
    public function payableOptions(): array
    {
        $stages = $this->stages();
        $options = [];

        foreach ($this->visiblePlans() as $planIndex => $plan) {
            foreach ($plan['tiers'] as $tierIndex => $tier) {
                if ((int) $tier['price'] <= 0) {
                    continue;
                }

                $options[] = [
                    'index' => count($options),
                    'plan_index' => $planIndex,
                    'tier_index' => $tierIndex,
                    'stage_label' => $stages[$plan['stage']]['label'] ?? '',
                    'plan_name' => $plan['name'],
                    'tier_label' => $tier['label'],
                    'price' => (int) $tier['price'],
                ];
            }
        }

        return $options;
    }

    /** Resolve one payable option by its flat index. Null if out of range. */
    public function payableOptionAt(int $index): ?array
    {
        return $this->payableOptions()[$index] ?? null;
    }

    /**
     * The order-line name for a payable option — e.g.
     * "Career Counselling · Class 10–12 · Explore (3 Sessions)". Built here so
     * the page, the resolver and the CRM enrolment row all read the same way.
     */
    public static function optionLabel(array $option): string
    {
        $parts = array_values(array_filter([
            'Career Counselling',
            trim((string) ($option['stage_label'] ?? '')),
            trim((string) ($option['plan_name'] ?? '')),
        ], fn (string $part) => $part !== ''));

        $label = implode(' · ', $parts);
        $tier = trim((string) ($option['tier_label'] ?? ''));

        return mb_substr($tier !== '' ? $label.' ('.$tier.')' : $label, 0, 200);
    }

    /** Coerce a raw array into the canonical shape with sane bounds. */
    private function normalize(array $data): array
    {
        $defaults = $this->defaults();

        // Seed a default ONLY when the key is absent from the input. A key that
        // is present but empty/null stays empty — so clearing a field in the CMS
        // actually blanks it, instead of snapping back to the seed text. (The
        // save form always submits every field, and ConvertEmptyStringsToNull
        // turns a cleared box into null, which must NOT trigger the fallback.)
        $textField = static function (array $src, string $key, string $default, int $max): string {
            $value = array_key_exists($key, $src) ? (string) ($src[$key] ?? '') : $default;

            return mb_substr(trim($value), 0, $max);
        };

        $headingIn = is_array($data['heading'] ?? null) ? $data['heading'] : [];
        $heading = [
            'eyebrow' => $textField($headingIn, 'eyebrow', $defaults['heading']['eyebrow'], 60),
            'title' => $textField($headingIn, 'title', $defaults['heading']['title'], 140),
            'subtitle' => $textField($headingIn, 'subtitle', $defaults['heading']['subtitle'], 240),
        ];

        $stages = [];
        foreach ($data['stages'] ?? [] as $stage) {
            $label = trim((string) (is_array($stage) ? ($stage['label'] ?? '') : $stage));
            if ($label === '') {
                continue; // a stage with no label is how "remove" persists
            }
            $stages[] = ['label' => mb_substr($label, 0, 60)];
        }
        $stages = array_slice($stages, 0, self::MAX_STAGES);
        if ($stages === []) {
            $stages = $defaults['stages']; // the tab strip needs at least one tab
        }

        $plans = [];
        foreach ($data['plans'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue; // a row with no name is how "remove" persists
            }

            $plans[] = [
                'stage' => max(0, min(count($stages) - 1, (int) ($row['stage'] ?? 0))),
                'name' => mb_substr($name, 0, 60),
                'subtitle' => mb_substr(trim((string) ($row['subtitle'] ?? '')), 0, 140),
                'badge' => mb_substr(trim((string) ($row['badge'] ?? '')), 0, 40),
                'featured' => (bool) ($row['featured'] ?? false),
                'visible' => (bool) ($row['visible'] ?? true),
                'features' => $this->normalizeFeatures(is_array($row['features'] ?? null) ? $row['features'] : []),
                'tiers' => $this->normalizeTiers(is_array($row['tiers'] ?? null) ? $row['tiers'] : []),
            ];
        }

        $paymentIn = is_array($data['payment'] ?? null) ? $data['payment'] : [];
        $accent = strtolower((string) ($paymentIn['accent'] ?? $defaults['payment']['accent']));
        if (! preg_match('/^#[0-9a-f]{6}$/', $accent)) {
            $accent = $defaults['payment']['accent'];
        }
        // Button labels fall back when cleared (a pay button needs a label); the
        // other copy fields may be blanked. Accent is validated above.
        $buttonLabel = mb_substr(trim((string) ($paymentIn['button_label'] ?? '')), 0, 40);
        $enquiryLabel = mb_substr(trim((string) ($paymentIn['enquiry_label'] ?? '')), 0, 40);
        $payment = [
            'title' => $textField($paymentIn, 'title', $defaults['payment']['title'], 140),
            'description' => $textField($paymentIn, 'description', $defaults['payment']['description'], 400),
            'button_label' => $buttonLabel !== '' ? $buttonLabel : $defaults['payment']['button_label'],
            'enquiry_label' => $enquiryLabel !== '' ? $enquiryLabel : $defaults['payment']['enquiry_label'],
            'note' => $textField($paymentIn, 'note', $defaults['payment']['note'], 300),
            'accent' => $accent,
        ];

        return [
            'heading' => $heading,
            'stages' => $stages,
            'plans' => $plans,
            'payment' => $payment,
        ];
    }

    /**
     * A plan's bullet list. Each row is a bolded lead-in plus the explanation,
     * either included (tick) or withheld from the tier (padlock).
     */
    private function normalizeFeatures(array $features): array
    {
        $clean = [];
        foreach ($features as $feature) {
            if (! is_array($feature)) {
                continue;
            }

            $title = trim((string) ($feature['title'] ?? ''));
            $text = trim((string) ($feature['text'] ?? ''));
            if ($title === '' && $text === '') {
                continue;
            }

            $clean[] = [
                'title' => mb_substr($title, 0, 80),
                'text' => mb_substr($text, 0, 240),
                'locked' => (bool) ($feature['locked'] ?? false),
            ];
        }

        return array_slice($clean, 0, self::MAX_FEATURES);
    }

    /**
     * A plan's session tiers. One tier = one price and one payable option; a
     * plan with a single tier renders no session picker. A plan that somehow
     * arrives with no tier at all keeps one zero-priced row so the CMS always
     * has a price box to type into (and the card falls back to "on request").
     */
    private function normalizeTiers(array $tiers): array
    {
        $clean = [];
        foreach ($tiers as $tier) {
            if (! is_array($tier)) {
                continue;
            }

            $label = trim((string) ($tier['label'] ?? ''));
            $rawPrice = (string) ($tier['price'] ?? '');
            if ($label === '' && trim($rawPrice) === '') {
                continue;
            }

            // Price stored as whole rupees (0 allowed = "fee on request").
            $price = (int) round((float) preg_replace('/[^0-9.]/', '', $rawPrice));

            $clean[] = [
                'label' => mb_substr($label, 0, 40),
                'price' => max(0, min(10_000_000, $price)),
            ];
        }

        $clean = array_slice($clean, 0, self::MAX_TIERS);

        return $clean === [] ? [['label' => '', 'price' => 0]] : $clean;
    }

    /**
     * Seed data — the plan roster and pricing from the counselling design the
     * client shared, so the section ships populated and immediately editable.
     */
    public function defaults(): array
    {
        $f = fn (string $title, string $text, bool $locked = false) => [
            'title' => $title, 'text' => $text, 'locked' => $locked,
        ];

        $careerContent = $f('Career Content', 'Well-researched information on hundreds of career options.');

        return [
            'heading' => [
                'eyebrow' => '',
                'title' => 'Plans & Pricing',
                'subtitle' => "Choose the right guidance program for your child's stage of school.",
            ],
            'stages' => [
                ['label' => 'Class 8–9'],
                ['label' => 'Class 10–12'],
            ],
            'plans' => [
                [
                    'stage' => 0,
                    'name' => 'Explore',
                    'subtitle' => 'Stream Assessment + Counselling',
                    'badge' => 'Bestselling',
                    'featured' => true,
                    'visible' => true,
                    'features' => [
                        $f('Stream Assessment', '4-dimensional assessment with top stream recommendations.'),
                        $careerContent,
                        $f('25-Page Stream Report', 'Best-fit stream matches and development plans.'),
                        $f('Career Counselling', 'Online session up to 60 minutes with certified coaches.'),
                    ],
                    'tiers' => [
                        ['label' => '1 Session', 'price' => 7000],
                        ['label' => '3 Sessions', 'price' => 11000],
                    ],
                ],
                [
                    'stage' => 0,
                    'name' => 'Learn',
                    'subtitle' => 'Stream Assessment',
                    'badge' => '',
                    'featured' => false,
                    'visible' => true,
                    'features' => [
                        $f('Stream Assessment', '4-dimensional assessment with top stream recommendations.'),
                        $careerContent,
                        $f('25-Page Stream Report', 'Best-fit stream matches and development plans.'),
                        $f('Career Counselling', 'Online session with certified coaches.', true),
                    ],
                    'tiers' => [
                        ['label' => '', 'price' => 2500],
                    ],
                ],
                [
                    'stage' => 1,
                    'name' => 'Explore',
                    'subtitle' => 'Career Assessment + Counselling',
                    'badge' => 'Bestselling',
                    'featured' => true,
                    'visible' => true,
                    'features' => [
                        $f('Career Assessment', '5-dimensional assessment with best-fit career recommendations.'),
                        $careerContent,
                        $f('34-Page Career Report', 'Top career matches and development plans.'),
                        $f('Career Counselling', 'Online session up to 60 minutes with certified coaches.'),
                    ],
                    'tiers' => [
                        ['label' => '1 Session', 'price' => 7000],
                        ['label' => '3 Sessions', 'price' => 11000],
                    ],
                ],
                [
                    'stage' => 1,
                    'name' => 'Learn',
                    'subtitle' => 'Career Assessment',
                    'badge' => '',
                    'featured' => false,
                    'visible' => true,
                    'features' => [
                        $f('Career Assessment', '5-dimensional assessment with best-fit recommendations.'),
                        $careerContent,
                        $f('34-Page Career Report', 'Top career matches and development plans.'),
                        $f('Career Counselling', '', true),
                        $f('Dedicated Career Mentor', '', true),
                    ],
                    'tiers' => [
                        ['label' => '', 'price' => 2500],
                    ],
                ],
            ],
            'payment' => [
                'title' => 'Confirm your counselling plan',
                'description' => 'Tell us who the session is for and pay securely — the amount shown is the exact plan fee. Our team calls within one working day to schedule the session.',
                'button_label' => 'Pay securely',
                'enquiry_label' => 'Request a callback',
                'note' => 'Payments are processed by Razorpay. Your card details never touch our servers.',
                'accent' => '#ff5e32',
            ],
        ];
    }

    private function write(array $data): void
    {
        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0775, true);
        }

        file_put_contents(
            $this->path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
