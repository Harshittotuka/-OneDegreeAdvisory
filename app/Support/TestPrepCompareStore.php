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
            $seed = $this->normalize($this->defaults());
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

    /**
     * Visible programs that also have "exam info" popup content filled in —
     * these are the ones that render as a chip in the payment block's exam
     * strip. A program with price/duration but no popup title/tagline is
     * still a valid comparison row; it simply has no chip.
     */
    public function examPrograms(): array
    {
        return array_values(array_filter(
            $this->visiblePrograms(),
            fn (array $p) => trim($p['details']['title'] ?? '') !== '' || trim($p['details']['tagline'] ?? '') !== ''
        ));
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
                'details' => $this->normalizeDetails(is_array($row['details'] ?? null) ? $row['details'] : []),
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
     * A program's optional "exam info" popup content. Entirely optional — a
     * program with no title/tagline simply shows no chip in the payment
     * block's exam strip (see TestPrepCompareStore::examPrograms()).
     */
    private function normalizeDetails(array $details): array
    {
        $facts = [];
        foreach ($details['facts'] ?? [] as $fact) {
            $label = trim((string) (is_array($fact) ? ($fact[0] ?? $fact['label'] ?? '') : ''));
            $value = trim((string) (is_array($fact) ? ($fact[1] ?? $fact['value'] ?? '') : ''));
            if ($label === '' && $value === '') {
                continue;
            }
            $facts[] = [mb_substr($label, 0, 40), mb_substr($value, 0, 60)];
        }

        $syllabus = [];
        foreach ($details['syllabus'] ?? [] as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            $syllabus[] = mb_substr($line, 0, 200);
        }

        return [
            'eyebrow' => mb_substr(trim((string) ($details['eyebrow'] ?? '')), 0, 60),
            'title' => mb_substr(trim((string) ($details['title'] ?? '')), 0, 140),
            'tagline' => mb_substr(trim((string) ($details['tagline'] ?? '')), 0, 200),
            'facts' => array_slice($facts, 0, 8),
            'advantage' => mb_substr(trim((string) ($details['advantage'] ?? '')), 0, 600),
            'syllabus' => array_slice($syllabus, 0, 8),
            'source' => mb_substr(trim((string) ($details['source'] ?? '')), 0, 200),
        ];
    }

    /**
     * Seed data — the real program roster and pricing from the standalone
     * "boarding pass" microsite, so the section ships populated and editable.
     */
    public function defaults(): array
    {
        $p = function (string $name, int $price, float $months, string $badge = '', array $details = []) {
            return [
                'name' => $name, 'price' => $price, 'months' => $months, 'badge' => $badge, 'visible' => true,
                'details' => $details,
            ];
        };

        $d = function (string $eyebrow, string $title, string $tagline, array $facts, string $advantage, array $syllabus, string $source) {
            return [
                'eyebrow' => $eyebrow, 'title' => $title, 'tagline' => $tagline, 'facts' => $facts,
                'advantage' => $advantage, 'syllabus' => $syllabus, 'source' => $source,
            ];
        };

        return [
            'style' => 'bars',
            'heading' => [
                'eyebrow' => 'Compare',
                'title' => 'See the commitment at a glance',
                'subtitle' => 'Toggle to compare every program by price or by course duration — then enrol securely below.',
            ],
            'programs' => [
                $p('GRE', 18000, 2.5, '', $d(
                    'GRE', 'GRE General Test',
                    'A graduate admissions test used for master\'s, PhD and some MBA programs.',
                    [['Verbal / Quant', '130-170 each'], ['Analytical Writing', '0-6'], ['Duration', '~1 hr 58 min'], ['Delivery', 'Computer-based']],
                    'Required or accepted by many graduate schools worldwide for master\'s and PhD programs, and by some MBA programs as a GMAT alternative.',
                    ['Analytical Writing - one issue task', 'Verbal Reasoning - reading comprehension and text completion', 'Quantitative Reasoning - arithmetic, algebra, geometry and data analysis'],
                    'General exam-format information - verify current details with the official GRE program.'
                )),
                $p('GMAT', 18000, 3, '', $d(
                    'GMAT', 'GMAT - Graduate Management Admission Test',
                    'A computer-adaptive test built for MBA and business master\'s admissions.',
                    [['Score', '205-805 total'], ['Sections', 'Quant, Verbal & Data Insights'], ['Duration', '~2 hrs 15 min'], ['Essay/IR', 'No longer scored separately']],
                    'The primary admissions test for MBA and specialised business master\'s programs globally, with a strong focus on reasoning, data interpretation and business-school readiness.',
                    ['Quantitative Reasoning - problem solving', 'Verbal Reasoning - reading comprehension and critical reasoning', 'Data Insights - data sufficiency, tables, graphs and multi-source reasoning'],
                    'General exam-format information - verify current details with the official GMAT program.'
                )),
                $p('SAT', 18000, 1.5, 'Popular', $d(
                    'SAT', 'SAT - Digital Scholastic Assessment Test',
                    'A fully digital, adaptive US undergraduate admissions test.',
                    [['Score', '400-1600 total'], ['Sections', 'Reading & Writing + Math'], ['Duration', '~2 hrs 14 min'], ['Penalty', 'None for wrong answers']],
                    'Used for undergraduate admissions and merit scholarships at US universities. The Digital SAT is section-adaptive, so early module performance influences later module difficulty.',
                    ['Reading & Writing - 2 adaptive modules', 'Math - 2 adaptive modules with calculator support', 'Evidence, expression, algebra, data and problem solving'],
                    'General exam-format information - verify current details with the official College Board SAT program.'
                )),
                $p('ACT', 18000, 1.5, '', $d(
                    'ACT', 'ACT - American College Testing',
                    'A curriculum-based US admissions test with an optional Writing test.',
                    [['Score', '1-36 composite'], ['Sections', 'English, Math, Reading, Science'], ['Composite', 'Average of 4 section scores'], ['Writing', 'Optional essay']],
                    'A well-established alternative to the SAT for US admissions; many universities accept either test. The Science section can suit students confident in data interpretation.',
                    ['English - grammar, usage and rhetorical skills', 'Math - algebra, geometry and trigonometry', 'Reading - comprehension across passage types', 'Science - interpreting data, experiments and viewpoints'],
                    'General exam-format information - verify current details with the official ACT program.'
                )),
                $p('IELTS', 4000, 1, 'Best value', $d(
                    'IELTS', 'IELTS - International English Language Testing System',
                    'The world\'s most widely taken English proficiency test for study, work and migration.',
                    [['Score', 'Band 0-9 (0.5 increments)'], ['Duration', '~2 hrs 44 min'], ['Versions', 'Academic & General Training'], ['Validity', '2 years']],
                    'Accepted by universities, employers and immigration authorities across the UK, Canada, Australia, New Zealand and beyond. It is the only major English test with a face-to-face Speaking interview, and results are issued as a Band Score rather than pass/fail.',
                    ['Listening (~30 min) - 4 recorded sections, 40 questions', 'Reading (60 min) - 3 passages or texts, 40 questions', 'Writing (60 min) - a report/letter and an essay', 'Speaking (11-14 min) - face-to-face interview in 3 parts'],
                    'General exam-format information - verify current details with the official IELTS test body.'
                )),
                $p('TOEFL', 4000, 0.5, '', $d(
                    'TOEFL iBT', 'TOEFL iBT - Test of English as a Foreign Language',
                    'A widely accepted internet-based English test for university admissions.',
                    [['Score', '0-120 scale'], ['Duration', 'Under 2 hrs'], ['Format', 'Reading, Listening, Speaking, Writing'], ['Recognition', 'Trusted by US & Canadian universities']],
                    'Widely accepted for university admissions in the US and Canada, with academic tasks designed around classroom listening, reading, speaking and writing.',
                    ['Reading - academic passages and questions', 'Listening - lectures and conversations', 'Speaking - independent and integrated responses', 'Writing - academic discussion and integrated writing tasks'],
                    'General exam-format information - verify current details with the official TOEFL test body.'
                )),
                $p('PTE', 4000, 1, '', $d(
                    'PTE Academic', 'PTE - Pearson Test of English Academic',
                    'A fully computer-delivered, AI-scored English test known for fast turnaround.',
                    [['Score', '10-90 (Global Scale of English)'], ['Duration', '~2 hrs'], ['Delivery', '100% computer-based'], ['Results', 'Typically within 1-2 days']],
                    'Because every part of the test, including Speaking, is scored by AI, results arrive far faster than many English tests. It is widely accepted by universities and immigration authorities across major study destinations.',
                    ['Speaking & Writing - read aloud, repeat, describe image, essay and more', 'Reading - multiple choice, reorder paragraphs, fill in blanks', 'Listening - summarise, fill in blanks, highlight correct summary'],
                    'General exam-format information - verify current details with the official PTE test body.'
                )),
                $p('Duolingo', 4000, 0.5, '', $d(
                    'Duolingo English Test', 'Duolingo - Online English Proficiency Test',
                    'A convenient online English test used by many institutions for admissions screening.',
                    [['Score', '10-160 scale'], ['Delivery', 'Online'], ['Format', 'Adaptive test + writing/speaking sample'], ['Best for', 'Fast, flexible English proof']],
                    'Duolingo is useful when students need a flexible English-proficiency option with remote testing and quick planning around application timelines.',
                    ['Literacy - reading and vocabulary tasks', 'Comprehension - listening and meaning-based questions', 'Conversation - speaking prompts and responses', 'Production - writing sample and extended responses'],
                    'General exam-format information - verify current details with the official Duolingo English Test body.'
                )),
                $p('German A1', 12000, 2, '', $d(
                    'German Language', 'German A1-B1 (CEFR Levels)',
                    'Structured beginner-to-intermediate German, aligned to the Common European Framework.',
                    [['Levels', 'A1 -> A2 -> B1'], ['Framework', 'CEFR'], ['Focus', 'Daily communication'], ['Typical need', 'Visas / vocational training']],
                    'A1-B1 German is commonly required for German student visas, vocational-training applications and job-seeker pathways. Recognised language certificates can support embassy and institution requirements.',
                    ['Grammar, vocabulary and pronunciation for each level', 'Reading and listening comprehension', 'Writing - everyday letters, emails and short texts', 'Speaking - everyday conversation and exam tasks'],
                    'General language-format information - verify current certificate requirements with the official certifying body.'
                )),
                $p('German A2', 12000, 2),
                $p('German B1', 18000, 3),
                $p('French A1', 12000, 2, '', $d(
                    'French Language', 'French - Beginner to Intermediate Pathway',
                    'French language coaching for academic, visa and everyday communication goals.',
                    [['Levels', 'A1 -> A2 -> B1'], ['Framework', 'CEFR'], ['Skills', 'Reading, Listening, Writing, Speaking'], ['Best for', 'French-taught and dual-language routes']],
                    'French proficiency helps students access French-taught, bilingual and scholarship-linked opportunities while strengthening day-to-day communication for study abroad.',
                    ['Grammar, vocabulary and pronunciation', 'Reading and listening comprehension', 'Writing - emails, notes and short essays', 'Speaking - guided conversation and exam-style prompts'],
                    'General language-format information - verify current certificate requirements with the official certifying body.'
                )),
                $p('French A2', 12000, 2),
                $p('French B1', 18000, 3),
                $p('Japanese N5', 12000, 2.5, '', $d(
                    'Japanese Language', 'Japanese - Foundation Levels',
                    'Beginner Japanese for students planning academic, work or cultural pathways in Japan.',
                    [['Levels', 'N5 -> N4 foundation'], ['Framework', 'JLPT-oriented'], ['Skills', 'Vocabulary, grammar, reading, listening'], ['Best for', 'Japan study pathways']],
                    'Japanese foundation training helps students build the language confidence needed for daily life, interviews and future progression toward recognised proficiency levels.',
                    ['Hiragana, Katakana and essential Kanji', 'Core grammar and sentence patterns', 'Reading and listening practice', 'Conversation drills for everyday situations'],
                    'General language-format information - verify current certificate requirements with the official certifying body.'
                )),
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

        $written = file_put_contents(
            $this->path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        if ($written === false) {
            throw new \RuntimeException('Could not save the Test Prep Compare CMS data.');
        }

        app(CmsCrmBackupManager::class)->markDirty('cms-json');
    }
}
