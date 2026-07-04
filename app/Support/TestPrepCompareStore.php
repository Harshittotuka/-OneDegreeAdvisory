<?php

namespace App\Support;

/**
 * File-backed store for the "Compare — see the commitment at a glance" section
 * and its paired payment block on the Test Preparation page.
 *
 * Everything the CMS tab manages lives in one editable JSON file so there is no
 * database dependency: the section heading, the chosen visual style (one of four
 * variants), the list of programs (name, category, price in whole rupees, course
 * duration in months, class count, optional badge, visibility) and the payment
 * block copy/accent.
 *
 * A single program row is the source of truth for BOTH the compare chart and the
 * payment options — so a price shown in the bars is exactly what the customer is
 * charged. The amount is always re-derived server-side from this file at order
 * time (see PaymentBlockResolver), never taken from the browser.
 */
class TestPrepCompareStore
{
    /**
     * Sentinel "page slug" under which compare payments are recorded in the
     * payment_attempts table and resolved by PaymentBlockResolver. Mirrors the
     * brief-page slug shape (^[a-z0-9-]+$) so PaymentController validation passes.
     */
    public const PAGE_SLUG = 'test-prep-compare';

    /** The one payment block on this virtual page. */
    public const BLOCK_ID = 'compare';

    /** The four selectable visual styles. Variant 1 ("bars") is the current look. */
    public const STYLES = ['bars', 'cards', 'table', 'stack'];

    private string $path;

    /** Memo so repeated get() calls on one instance read disk once. */
    private ?array $cache = null;

    public function __construct()
    {
        $this->path = storage_path('app/test-prep-compare.json');
    }

    /** The full config, with defaults filled in for any missing keys. */
    public function get(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (! is_file($this->path)) {
            $seed = $this->defaults();
            $this->write($seed);

            return $this->cache = $seed;
        }

        $data = json_decode((string) file_get_contents($this->path), true);

        return $this->cache = $this->normalize(is_array($data) ? $data : []);
    }

    /** Only the programs that should appear on the public site, in stored order. */
    public function visiblePrograms(): array
    {
        return array_values(array_filter(
            $this->get()['programs'],
            fn (array $p) => $p['visible'] ?? true
        ));
    }

    /**
     * Resolve one visible program by its index in the visible list. Used by the
     * payment resolver: the client sends the visible-list index it rendered, and
     * we look up the authoritative price here. Returns null if out of range.
     */
    public function visibleProgramAt(int $index): ?array
    {
        return $this->visiblePrograms()[$index] ?? null;
    }

    public function save(array $data): array
    {
        $clean = $this->normalize($data);
        $this->write($clean);
        $this->cache = $clean;

        return $clean;
    }

    /** Coerce a raw array into the canonical shape with sane bounds. */
    private function normalize(array $data): array
    {
        $defaults = $this->defaults();

        $style = (string) ($data['style'] ?? $defaults['style']);
        if (! in_array($style, self::STYLES, true)) {
            $style = $defaults['style'];
        }

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

        $programs = [];
        foreach ($data['programs'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue; // a row with no name is how "remove" persists
            }

            // Price stored as whole rupees (0 allowed = "price on request"/free).
            $price = (int) round((float) preg_replace('/[^0-9.]/', '', (string) ($row['price'] ?? 0)));
            $price = max(0, min(10_000_000, $price));

            // Duration in months; halves are meaningful (0.5 mo courses exist).
            $months = round((float) ($row['months'] ?? 0), 1);
            $months = max(0.0, min(60.0, $months));

            $programs[] = [
                'name' => mb_substr($name, 0, 80),
                'price' => $price,
                'months' => $months,
                'badge' => mb_substr(trim((string) ($row['badge'] ?? '')), 0, 40),
                'visible' => (bool) ($row['visible'] ?? true),
            ];
        }

        $paymentIn = is_array($data['payment'] ?? null) ? $data['payment'] : [];
        $accent = strtolower((string) ($paymentIn['accent'] ?? $defaults['payment']['accent']));
        if (! preg_match('/^#[0-9a-f]{6}$/', $accent)) {
            $accent = $defaults['payment']['accent'];
        }
        // Button label falls back when cleared (a pay button needs a label); the
        // other copy fields may be blanked. Accent is validated above.
        $buttonLabel = mb_substr(trim((string) ($paymentIn['button_label'] ?? '')), 0, 40);
        $payment = [
            'eyebrow' => $textField($paymentIn, 'eyebrow', $defaults['payment']['eyebrow'], 60),
            'title' => $textField($paymentIn, 'title', $defaults['payment']['title'], 140),
            'description' => $textField($paymentIn, 'description', $defaults['payment']['description'], 400),
            'button_label' => $buttonLabel !== '' ? $buttonLabel : $defaults['payment']['button_label'],
            'note' => $textField($paymentIn, 'note', $defaults['payment']['note'], 300),
            'accent' => $accent,
        ];

        return [
            'style' => $style,
            'heading' => $heading,
            'programs' => $programs,
            'payment' => $payment,
        ];
    }

    /**
     * Seed data — the real program roster and pricing from the standalone
     * "boarding pass" microsite, so the section ships populated and editable.
     */
    public function defaults(): array
    {
        $p = fn (string $name, int $price, float $months, string $badge = '') => [
            'name' => $name, 'price' => $price, 'months' => $months, 'badge' => $badge, 'visible' => true,
        ];

        return [
            'style' => 'bars',
            'heading' => [
                'eyebrow' => 'Compare',
                'title' => 'See the commitment at a glance',
                'subtitle' => 'Toggle to compare every program by price or by course duration — then enrol securely below.',
            ],
            'programs' => [
                $p('GRE', 18000, 2.5),
                $p('GMAT', 18000, 3),
                $p('SAT', 18000, 1.5, 'Popular'),
                $p('ACT', 18000, 1.5),
                $p('IELTS', 4000, 1, 'Best value'),
                $p('TOEFL', 4000, 0.5),
                $p('PTE', 4000, 1),
                $p('Duolingo', 4000, 0.5),
                $p('German A1', 12000, 2),
                $p('German A2', 12000, 2),
                $p('German B1', 18000, 3),
                $p('French A1', 12000, 2),
                $p('French A2', 12000, 2),
                $p('French B1', 18000, 3),
                $p('Japanese N5', 12000, 2.5),
                $p('Japanese N4', 12000, 2.5),
            ],
            'payment' => [
                'eyebrow' => 'Enrol now',
                'title' => 'Reserve your seat online',
                'description' => 'Pick your program and pay securely to lock your batch. The amount shown is the exact course fee.',
                'button_label' => 'Pay securely',
                'note' => 'Payments are processed by Razorpay. Your card details never touch our servers.',
                'accent' => '#ff5a2e',
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
