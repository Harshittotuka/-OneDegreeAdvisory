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
 *  1. `audio` is keyed to a RECORDING, not to a position. Only the ten questions
 *     that were recorded carry one (public/assets/audio/visa-mock-interview/qN.mp3).
 *     The page numbered clips positionally before, so widening the bank would
 *     have shifted every voiceover onto the wrong question.
 *  2. `free` marks exactly those same ten. The public page samples the free pool
 *     only, so an anonymous visitor gets the identical ten questions and ten
 *     voiceovers they got before this bank was widened.
 *
 * Questions without a recording are spoken by the browser's speech engine.
 * To promote one, record the clip and add its `audio` key — nothing else changes.
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
            ['id' => 'why-course', 'q' => 'Why did you choose this course?', 'audio' => 'q2', 'free' => true],
            ['id' => 'academic-background', 'q' => 'Explain your academic background.'],
            ['id' => 'field-change', 'q' => 'Why are you changing your field (if applicable)?'],
            ['id' => 'career-goals', 'q' => 'What are your future career goals?'],
        ]],
        ['cat' => 'University & Course Related', 'items' => [
            ['id' => 'which-university', 'q' => 'Which university are you going to?'],
            ['id' => 'why-university', 'q' => 'Why did you choose this university?', 'audio' => 'q3', 'free' => true],
            ['id' => 'why-here-not-elsewhere', 'q' => 'Why this country and not another country?'],
            ['id' => 'university-knowledge', 'q' => 'What do you know about your university?'],
            ['id' => 'course-duration', 'q' => 'What is the duration of your course?', 'audio' => 'q4', 'free' => true],
            ['id' => 'course-start', 'q' => 'When does your course start?'],
            ['id' => 'course-subjects', 'q' => 'What subjects will you study?'],
            ['id' => 'course-link-previous', 'q' => 'How is this course related to your previous studies?'],
            ['id' => 'other-applications', 'q' => 'Did you apply to any other universities?'],
        ]],
        ['cat' => 'Country Knowledge', 'items' => [
            ['id' => 'why-country', 'q' => 'Why did you select this country for higher education?', 'audio' => 'q5', 'free' => true],
            ['id' => 'country-knowledge', 'q' => 'What do you know about this country?'],
            ['id' => 'university-city', 'q' => 'Which city is your university located in?'],
            ['id' => 'city-lifestyle', 'q' => 'What is the climate and lifestyle of that city?'],
            ['id' => 'local-language', 'q' => 'Do you know the local language?'],
            ['id' => 'post-study-opportunities', 'q' => 'What opportunities are available after completing your studies?'],
        ]],
        ['cat' => 'Financial Questions', 'items' => [
            ['id' => 'sponsor', 'q' => 'Who is sponsoring your education?', 'audio' => 'q6', 'free' => true],
            ['id' => 'sponsor-occupation', 'q' => 'What does your sponsor do?'],
            ['id' => 'family-income', 'q' => "What is your family's annual income?"],
            ['id' => 'pay-fees', 'q' => 'How will you pay your tuition fees and living expenses?', 'audio' => 'q7', 'free' => true],
            ['id' => 'scholarship', 'q' => 'Do you have a scholarship?'],
            ['id' => 'scholarship-amount', 'q' => 'What is the amount of your scholarship?'],
            ['id' => 'funds-shown', 'q' => 'How much money have you shown for your visa application?'],
            ['id' => 'education-loan', 'q' => 'Do you have an education loan?'],
        ]],
        ['cat' => 'Accommodation & Travel', 'items' => [
            ['id' => 'accommodation', 'q' => 'Where will you stay after arriving?', 'audio' => 'q8', 'free' => true],
            ['id' => 'accommodation-proof', 'q' => 'Do you have accommodation proof?'],
            ['id' => 'accommodation-arranged-by', 'q' => 'Who arranged your accommodation?'],
            ['id' => 'travel-date', 'q' => 'When are you planning to travel?'],
            ['id' => 'flight-booked', 'q' => 'Have you booked your flight ticket?'],
        ]],
        ['cat' => 'Future Plans & Intentions', 'items' => [
            ['id' => 'after-studies', 'q' => 'What will you do after completing your studies?', 'audio' => 'q9', 'free' => true],
            ['id' => 'work-after-graduation', 'q' => 'Do you plan to work in the country after graduation?'],
            ['id' => 'settle-abroad', 'q' => 'Do you want to settle in this country?'],
            ['id' => 'return-home', 'q' => 'Will you return to your home country after your studies?', 'audio' => 'q10', 'free' => true],
            ['id' => 'degree-career-help', 'q' => 'How will this degree help your career?'],
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
