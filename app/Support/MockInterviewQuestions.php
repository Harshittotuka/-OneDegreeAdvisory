<?php

namespace App\Support;

/**
 * The visa mock-interview question bank.
 *
 * This used to live as a JS const inside the page, which meant the extended
 * (15/20/39) rounds could be unlocked from devtools. The bank now lives here and
 * an invited round receives its queue from the server, so the question count a
 * CRM invite grants is actually enforced.
 *
 * Two things are load-bearing:
 *
 *  1. `audio` is an explicit key naming the file a clip lives in
 *     (public/assets/audio/visa-mock-interview/qN.mp3). The clips happen to be
 *     numbered by the position their question holds below, so today qN sits at
 *     position N — but that is a filing convention, not a rule the code relies
 *     on, and it only survives while the bank stays frozen. NEVER derive the key
 *     from the position: the page did exactly that once, and widening the bank
 *     silently shifted every voiceover onto the wrong question. Insert a
 *     question and the convention breaks for everything after it, while these
 *     explicit keys keep pointing at the right audio.
 *  2. `free` marks the ten questions the public page samples, and is independent
 *     of `audio`. Most of the bank is now recorded, but the free pool stays at
 *     those same ten so an anonymous visitor gets the identical round they got
 *     before the bank was widened — recording a clip must never widen it.
 *
 * Questions without a recording are spoken by the browser's speech engine.
 * To promote one, record the clip and add its `audio` key — nothing else changes.
 *
 * Seven questions are still unrecorded, and their numbers are the gaps in the
 * folder: q2, q4, q5, q6, q7, q9 and q10.
 */
class MockInterviewQuestions
{
    /** Question counts a CRM invite may grant. */
    public const INVITE_COUNTS = [15, 20, 39];

    /** Counts the public (un-invited) page may run. */
    public const FREE_COUNTS = [5, 10];

    /**
     * The full bank, in interview order, grouped by category.
     *
     * @var list<array{cat: string, items: list<array{id: string, q: string, audio?: string, free?: bool}>}>
     */
    private const BANK = [
        ['cat' => 'Personal & Academic Background', 'items' => [
            ['id' => 'about-yourself', 'q' => 'Tell me about yourself.', 'audio' => 'q1', 'free' => true],
            ['id' => 'previous-education', 'q' => 'What did you study in your previous education?'],
            ['id' => 'why-course', 'q' => 'Why did you choose this course?', 'audio' => 'q3', 'free' => true],
            ['id' => 'academic-background', 'q' => 'Explain your academic background.'],
            ['id' => 'field-change', 'q' => 'Why are you changing your field (if applicable)?'],
            ['id' => 'career-goals', 'q' => 'What are your future career goals?'],
        ]],
        ['cat' => 'University & Course Related', 'items' => [
            ['id' => 'which-university', 'q' => 'Which university are you going to?'],
            ['id' => 'why-university', 'q' => 'Why did you choose this university?', 'audio' => 'q8', 'free' => true],
            ['id' => 'why-here-not-elsewhere', 'q' => 'Why this country and not another country?'],
            ['id' => 'university-knowledge', 'q' => 'What do you know about your university?'],
            ['id' => 'course-duration', 'q' => 'What is the duration of your course?', 'audio' => 'q11', 'free' => true],
            ['id' => 'course-start', 'q' => 'When does your course start?', 'audio' => 'q12'],
            ['id' => 'course-subjects', 'q' => 'What subjects will you study?', 'audio' => 'q13'],
            ['id' => 'course-link-previous', 'q' => 'How is this course related to your previous studies?', 'audio' => 'q14'],
            ['id' => 'other-applications', 'q' => 'Did you apply to any other universities?', 'audio' => 'q15'],
        ]],
        ['cat' => 'Country Knowledge', 'items' => [
            ['id' => 'why-country', 'q' => 'Why did you select this country for higher education?', 'audio' => 'q16', 'free' => true],
            ['id' => 'country-knowledge', 'q' => 'What do you know about this country?', 'audio' => 'q17'],
            ['id' => 'university-city', 'q' => 'Which city is your university located in?', 'audio' => 'q18'],
            ['id' => 'city-lifestyle', 'q' => 'What is the climate and lifestyle of that city?', 'audio' => 'q19'],
            ['id' => 'local-language', 'q' => 'Do you know the local language?', 'audio' => 'q20'],
            ['id' => 'post-study-opportunities', 'q' => 'What opportunities are available after completing your studies?', 'audio' => 'q21'],
        ]],
        ['cat' => 'Financial Questions', 'items' => [
            ['id' => 'sponsor', 'q' => 'Who is sponsoring your education?', 'audio' => 'q22', 'free' => true],
            ['id' => 'sponsor-occupation', 'q' => 'What does your sponsor do?', 'audio' => 'q23'],
            ['id' => 'family-income', 'q' => "What is your family's annual income?", 'audio' => 'q24'],
            ['id' => 'pay-fees', 'q' => 'How will you pay your tuition fees and living expenses?', 'audio' => 'q25', 'free' => true],
            ['id' => 'scholarship', 'q' => 'Do you have a scholarship?', 'audio' => 'q26'],
            ['id' => 'scholarship-amount', 'q' => 'What is the amount of your scholarship?', 'audio' => 'q27'],
            ['id' => 'funds-shown', 'q' => 'How much money have you shown for your visa application?', 'audio' => 'q28'],
            ['id' => 'education-loan', 'q' => 'Do you have an education loan?', 'audio' => 'q29'],
        ]],
        ['cat' => 'Accommodation & Travel', 'items' => [
            ['id' => 'accommodation', 'q' => 'Where will you stay after arriving?', 'audio' => 'q30', 'free' => true],
            ['id' => 'accommodation-proof', 'q' => 'Do you have accommodation proof?', 'audio' => 'q31'],
            ['id' => 'accommodation-arranged-by', 'q' => 'Who arranged your accommodation?', 'audio' => 'q32'],
            ['id' => 'travel-date', 'q' => 'When are you planning to travel?', 'audio' => 'q33'],
            ['id' => 'flight-booked', 'q' => 'Have you booked your flight ticket?', 'audio' => 'q34'],
        ]],
        ['cat' => 'Future Plans & Intentions', 'items' => [
            ['id' => 'after-studies', 'q' => 'What will you do after completing your studies?', 'audio' => 'q35', 'free' => true],
            ['id' => 'work-after-graduation', 'q' => 'Do you plan to work in the country after graduation?', 'audio' => 'q36'],
            ['id' => 'settle-abroad', 'q' => 'Do you want to settle in this country?', 'audio' => 'q37'],
            ['id' => 'return-home', 'q' => 'Will you return to your home country after your studies?', 'audio' => 'q38', 'free' => true],
            ['id' => 'degree-career-help', 'q' => 'How will this degree help your career?', 'audio' => 'q39'],
        ]],
    ];

    /** Every question in the bank, flattened into interview order. */
    public static function all(): array
    {
        $flat = [];
        foreach (self::BANK as $group) {
            foreach ($group['items'] as $item) {
                $flat[] = [
                    'id' => $item['id'],
                    'cat' => $group['cat'],
                    'q' => $item['q'],
                    'audio' => isset($item['audio'])
                        ? asset('assets/audio/visa-mock-interview/'.$item['audio'].'.mp3')
                        : '',
                    'free' => (bool) ($item['free'] ?? false),
                ];
            }
        }

        return $flat;
    }

    /** The ten recorded questions the public page runs. */
    public static function freePool(): array
    {
        return array_values(array_filter(self::all(), fn (array $q) => $q['free']));
    }

    public static function total(): int
    {
        return count(self::all());
    }

    /**
     * Build an interview queue of $count questions.
     *
     * $extended draws on the whole bank (an invited round); otherwise only the
     * recorded free pool. Sampling is the even-step walk the page used before,
     * so a free round of 5 or 10 is unchanged.
     */
    public static function queue(int $count, bool $extended = false): array
    {
        $pool = $extended ? self::all() : self::freePool();
        $count = max(1, $count);

        if ($count >= count($pool)) {
            return array_map(self::stripFlag(), $pool);
        }

        $step = count($pool) / $count;
        $picked = [];
        for ($i = 0; $i < $count; $i++) {
            $picked[] = $pool[(int) floor($i * $step)];
        }

        return array_map(self::stripFlag(), $picked);
    }

    /** The `free` flag is bank bookkeeping; the page has no use for it. */
    private static function stripFlag(): callable
    {
        return fn (array $q) => ['id' => $q['id'], 'cat' => $q['cat'], 'q' => $q['q'], 'audio' => $q['audio']];
    }
}
