<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class VisaMockAssessor
{
    private const SCORE_FIELDS = [
        'language',
        'grammar',
        'confidence',
        'clarity',
        'crispness',
        'relevance',
        'courseKnowledge',
        'universityKnowledge',
        'countryKnowledge',
        'financialAwareness',
        'overall',
    ];

    /**
     * Human-friendly label of whichever tier produced the last result
     * (e.g. "Groq llama-3.3-70b-versatile" or "On-server qwen2.5:1.5b").
     */
    private ?string $lastAssessorLabel = null;

    public function lastAssessorLabel(): ?string
    {
        return $this->lastAssessorLabel;
    }

    public function assess(array $input): ?array
    {
        if (! config('services.visa_mock_ai.enabled', true)) {
            return null;
        }

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $this->userPrompt($input)],
        ];
        $schema = $this->schema();
        $numCtx = (int) config('services.visa_mock_ai.num_ctx', 2048);
        $numPredict = (int) config('services.visa_mock_ai.num_predict', 700);
        $ollamaTimeout = (float) config('services.visa_mock_ai.timeout', 300);

        foreach ($this->chain() as $tier) {
            $content = $this->requestContent($tier, $messages, $schema, $numPredict, $numCtx, $ollamaTimeout, 'single assessment');
            if ($content === null) {
                continue;
            }

            try {
                $decoded = $this->decodeStructuredJson($content);
            } catch (JsonException) {
                continue;
            }

            $result = $this->sanitize($decoded);
            if ($result !== null) {
                $this->lastAssessorLabel = $tier['label'];

                return $result;
            }
        }

        return null;
    }

    /**
     * Assess every answered interview question in one generation.
     * Results are returned in exactly the same order as the supplied answers.
     */
    public function assessBatch(array $inputs): ?array
    {
        if (! config('services.visa_mock_ai.enabled', true) || $inputs === []) {
            return null;
        }

        $batchSystem = $this->systemPrompt()."\n\nBatch output rules:\n"
            ."- Assess every supplied answer exactly once and preserve its array order.\n"
            ."- In each item, s contains scores in this exact order: language, grammar, confidence, clarity, crispness, relevance, courseKnowledge, universityKnowledge, countryKnowledge, financialAwareness, overall.\n"
            ."- Use null only for knowledge fields not tested; overall must be a number.\n"
            ."- g is one strength, i is one improvement, m is one mistake, and b is a better answer.\n"
            ."- Keep g, i, and m below 14 words each. Keep b below 32 words and two sentences.";

        $messages = [
            ['role' => 'system', 'content' => $batchSystem],
            ['role' => 'user', 'content' => $this->batchUserPrompt($inputs)],
        ];
        $schema = $this->batchSchema(count($inputs));
        $numCtx = (int) config('services.visa_mock_ai.batch_num_ctx', 4096);
        $numPredict = (int) config('services.visa_mock_ai.batch_num_predict', 2400);
        $ollamaTimeout = (float) config('services.visa_mock_ai.batch_timeout', 600);

        foreach ($this->chain() as $tier) {
            $content = $this->requestContent($tier, $messages, $schema, $numPredict, $numCtx, $ollamaTimeout, 'batch assessment');
            if ($content === null) {
                continue;
            }

            try {
                $decoded = $this->decodeStructuredJson($content);
            } catch (JsonException) {
                continue;
            }

            $assessments = $decoded['assessments'] ?? null;
            if (! is_array($assessments) || count($assessments) !== count($inputs)) {
                Log::warning('Visa mock '.$tier['label'].' batch assessment returned an incomplete set.', [
                    'expected' => count($inputs),
                    'actual' => is_array($assessments) ? count($assessments) : null,
                ]);

                continue;
            }

            $sanitized = [];
            $valid = true;
            foreach ($assessments as $index => $assessment) {
                $item = $this->sanitizeBatchItem($assessment);
                if ($item === null) {
                    Log::warning('Visa mock '.$tier['label'].' batch assessment returned an invalid item.', [
                        'index' => $index,
                        'score_count' => is_array($assessment['s'] ?? null) ? count($assessment['s']) : null,
                    ]);
                    $valid = false;

                    break;
                }
                $sanitized[] = $item;
            }

            if (! $valid) {
                continue;
            }

            $this->lastAssessorLabel = $tier['label'];

            return $sanitized;
        }

        return null;
    }

    /**
     * Ordered fallback chain. Each tier is tried in turn until one returns a
     * valid assessment. Remote Groq tiers come first (fast + high quality) and
     * are skipped entirely when no API key is configured; the local Ollama tier
     * is the final, always-available fallback.
     *
     * @return list<array<string, mixed>>
     */
    private function chain(): array
    {
        $tiers = [];

        $groqKey = trim((string) config('services.visa_mock_ai.groq.key'));
        $groqUrl = rtrim(trim((string) config('services.visa_mock_ai.groq.url', 'https://api.groq.com/openai/v1')), '/');

        if ($groqKey !== '' && $groqUrl !== '') {
            $groqModels = [
                trim((string) config('services.visa_mock_ai.groq.primary_model', 'llama-3.3-70b-versatile')),
                trim((string) config('services.visa_mock_ai.groq.fallback_model', 'llama-3.1-8b-instant')),
            ];

            foreach (array_values(array_unique(array_filter($groqModels))) as $model) {
                $tiers[] = [
                    'driver' => 'groq',
                    'url' => $groqUrl,
                    'key' => $groqKey,
                    'model' => $model,
                    'label' => 'Groq '.$model,
                    'connect_timeout' => (float) config('services.visa_mock_ai.groq.connect_timeout', 5),
                    'timeout' => (float) config('services.visa_mock_ai.groq.timeout', 45),
                ];
            }
        }

        $ollamaUrl = rtrim(trim((string) config('services.visa_mock_ai.url')), '/');
        $ollamaModel = trim((string) config('services.visa_mock_ai.model'));

        if ($ollamaUrl !== '' && $ollamaModel !== '') {
            $tiers[] = [
                'driver' => 'ollama',
                'url' => $ollamaUrl,
                'model' => $ollamaModel,
                'label' => 'On-server '.$ollamaModel,
                'connect_timeout' => (float) config('services.visa_mock_ai.connect_timeout', 1.5),
            ];
        }

        return $tiers;
    }

    /**
     * Perform one structured-output request against a single tier and return
     * the raw JSON content string, or null if the tier could not answer.
     */
    private function requestContent(array $tier, array $messages, array $schema, int $numPredict, int $numCtx, float $ollamaTimeout, string $operation): ?string
    {
        try {
            if ($tier['driver'] === 'groq') {
                // Groq's Llama models support json_object mode (guaranteed valid
                // JSON) but not json_schema enforcement, so the exact shape is
                // described in-prompt and validated afterwards by sanitize().
                $messages[0]['content'] .= "\n\nReturn only a single JSON object that matches this JSON schema exactly, with no markdown fences or commentary:\n"
                    .json_encode($schema, JSON_UNESCAPED_SLASHES);

                $response = Http::acceptJson()
                    ->withToken($tier['key'])
                    ->connectTimeout((float) $tier['connect_timeout'])
                    ->timeout((float) $tier['timeout'])
                    ->post($tier['url'].'/chat/completions', [
                        'model' => $tier['model'],
                        'messages' => $messages,
                        'temperature' => 0.1,
                        'top_p' => 0.85,
                        'max_tokens' => $numPredict,
                        'response_format' => ['type' => 'json_object'],
                    ]);

                if (! $response->successful()) {
                    $this->logFailure($tier, $operation, $response->status(), $response->body());

                    return null;
                }

                $content = $response->json('choices.0.message.content');
            } else {
                $response = Http::acceptJson()
                    ->connectTimeout((float) $tier['connect_timeout'])
                    ->timeout($ollamaTimeout)
                    ->post($tier['url'].'/api/chat', [
                        'model' => $tier['model'],
                        'stream' => false,
                        'keep_alive' => (string) config('services.visa_mock_ai.keep_alive', '30m'),
                        'format' => $schema,
                        'options' => [
                            'temperature' => 0.1,
                            'num_ctx' => $numCtx,
                            'num_predict' => $numPredict,
                            'top_p' => 0.85,
                        ],
                        'messages' => $messages,
                    ]);

                if (! $response->successful()) {
                    $this->logFailure($tier, $operation, $response->status(), $response->body());

                    return null;
                }

                $content = $response->json('message.content');
            }

            return is_string($content) && trim($content) !== '' ? $content : null;
        } catch (ConnectionException) {
            // Connection failures (local Ollama offline, Groq unreachable) are an
            // expected trigger to fall through to the next tier, not an error.
            return null;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * Worst-case PHP execution budget for the whole chain, so a slow tier
     * cannot make the endpoint exceed its time limit before falling back.
     */
    public function timeBudgetSeconds(bool $batch): int
    {
        $seconds = 0.0;
        $ollamaTimeout = (float) config('services.visa_mock_ai.'.($batch ? 'batch_timeout' : 'timeout'), $batch ? 600 : 300);

        foreach ($this->chain() as $tier) {
            $seconds += $tier['driver'] === 'groq'
                ? (float) ($tier['timeout'] ?? 45)
                : $ollamaTimeout;
        }

        return max(60, (int) ceil($seconds) + 15);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a careful student-visa mock interview assessor. Evaluate only the candidate's written or transcribed answer to the supplied question. Be constructive, concise, culturally neutral, and evidence-based.

Rules:
- Do not predict whether a visa will be approved or refused.
- Do not invent facts, personal details, university data, money amounts, or immigration rules.
- Reward a direct answer, relevant concrete detail, internal consistency, clarity, and credible study intent.
- Penalize vagueness, contradictions, irrelevant content, memorized-sounding language, missing requested facts, and unsupported claims.
- A short answer can be correct but should lose completeness points when the question reasonably needs detail.
- Use scores from 0 to 10. Use null for knowledge fields that the question does not test.
- The betterAnswer must be a safe adaptable example, using placeholders in square brackets for facts not supplied by the candidate.
- Keep good, improve, mistakes, and officerTip to one concise sentence each (maximum 35 words).
- Keep betterAnswer below 90 words and three sentences.
- Return only JSON matching the provided schema.
PROMPT;
    }

    private function userPrompt(array $input): string
    {
        $metrics = $input['metrics'] ?? [];

        return implode("\n", [
            'Category: '.($input['category'] ?? ''),
            'Question: '.($input['question'] ?? ''),
            'Expected evidence for this question: '.$this->rubricFor((string) ($input['question'] ?? '')),
            'Candidate answer: '.($input['answer'] ?? ''),
            'Interview mode: '.($input['mode'] ?? 'text'),
            'Destination supplied by candidate: '.(($input['destination'] ?? '') ?: 'not supplied'),
            'Observed word count: '.($metrics['wordCount'] ?? 'unknown'),
            'Observed answer duration seconds: '.($metrics['durationSec'] ?? 'unknown'),
            'Observed filler count: '.($metrics['fillerCount'] ?? 'unknown'),
            'Assess this answer now.',
        ]);
    }

    private function batchUserPrompt(array $inputs): string
    {
        $answers = [];

        foreach (array_values($inputs) as $index => $input) {
            $metrics = $input['metrics'] ?? [];
            $answers[] = [
                'position' => $index + 1,
                'category' => $input['category'] ?? '',
                'question' => $input['question'] ?? '',
                'expectedEvidence' => $this->rubricFor((string) ($input['question'] ?? '')),
                'candidateAnswer' => $input['answer'] ?? '',
                'mode' => $input['mode'] ?? 'text',
                'destination' => ($input['destination'] ?? '') ?: 'not supplied',
                'wordCount' => $metrics['wordCount'] ?? 'unknown',
                'durationSec' => $metrics['durationSec'] ?? 'unknown',
                'fillerCount' => $metrics['fillerCount'] ?? 'unknown',
            ];
        }

        return 'Assess all answers below in one batch. Return exactly '.count($answers)." assessments in the same order.\n\n".
            json_encode($answers, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function rubricFor(string $question): string
    {
        return match (Str::lower(trim($question))) {
            'tell me about yourself.' => 'Current academic status, relevant background or achievement, and a concise link to the planned study path.',
            'why did you choose this course?' => 'Specific course content or modules, connection to prior study or experience, and a credible career outcome.',
            'why did you choose this university?' => 'Named, verifiable university-specific features and a clear personal academic fit; generic prestige claims are weak.',
            'what is the duration of your course?' => 'A direct, exact duration, ideally consistent with start and completion timing.',
            'why did you select this country for higher education?' => 'Academic and course-specific reasons, a sensible comparison, and evidence of research; migration-only motives are weak.',
            'who is sponsoring your education?' => 'Sponsor relationship, credible source of income or funds, and willingness/capacity to pay.',
            'how will you pay your tuition fees and living expenses?' => 'Tuition and living-cost awareness, funding sources, available funds, and any loan or scholarship details.',
            'where will you stay after arriving?' => 'Accommodation type, location, arrangement or booking status, and practical awareness of travel to campus.',
            'what will you do after completing your studies?' => 'A specific, realistic career plan that uses the qualification and connects to opportunities in the home country.',
            'will you return to your home country after your studies?' => 'A direct answer supported by credible career, family, professional, or economic ties without exaggerated promises.',
            default => 'Answer the exact question directly, support it with concrete details, and stay consistent with the application documents.',
        };
    }

    private function schema(): array
    {
        $nullableScore = ['type' => ['number', 'null'], 'minimum' => 0, 'maximum' => 10];
        $scoreProperties = [];

        foreach (self::SCORE_FIELDS as $field) {
            $scoreProperties[$field] = $field === 'overall'
                ? ['type' => 'number', 'minimum' => 0, 'maximum' => 10]
                : $nullableScore;
        }

        return [
            'type' => 'object',
            'properties' => [
                'scores' => [
                    'type' => 'object',
                    'properties' => $scoreProperties,
                    'required' => self::SCORE_FIELDS,
                ],
                'good' => ['type' => 'string'],
                'improve' => ['type' => 'string'],
                'mistakes' => ['type' => 'string'],
                'betterAnswer' => ['type' => 'string'],
                'officerTip' => ['type' => 'string'],
                'scripted' => ['type' => 'boolean'],
            ],
            'required' => ['scores', 'good', 'improve', 'mistakes', 'betterAnswer', 'officerTip', 'scripted'],
        ];
    }

    private function batchSchema(int $count): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'assessments' => [
                    'type' => 'array',
                    'minItems' => $count,
                    'maxItems' => $count,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            's' => [
                                'type' => 'array',
                                'minItems' => count(self::SCORE_FIELDS),
                                'maxItems' => count(self::SCORE_FIELDS),
                                'items' => ['type' => ['number', 'null'], 'minimum' => 0, 'maximum' => 10],
                            ],
                            'g' => ['type' => 'string', 'maxLength' => 120],
                            'i' => ['type' => 'string', 'maxLength' => 120],
                            'm' => ['type' => 'string', 'maxLength' => 120],
                            'b' => ['type' => 'string', 'maxLength' => 280],
                        ],
                        'required' => ['s', 'g', 'i', 'm', 'b'],
                    ],
                ],
            ],
            'required' => ['assessments'],
        ];
    }

    private function sanitizeBatchItem(mixed $assessment): ?array
    {
        if (! is_array($assessment) || ! is_array($assessment['s'] ?? null) || count($assessment['s']) !== count(self::SCORE_FIELDS)) {
            return null;
        }

        $scores = array_combine(self::SCORE_FIELDS, array_values($assessment['s']));

        return $this->sanitize([
            'scores' => $scores,
            'good' => $assessment['g'] ?? '',
            'improve' => $assessment['i'] ?? '',
            'mistakes' => $assessment['m'] ?? '',
            'betterAnswer' => $assessment['b'] ?? '',
            'officerTip' => '',
            'scripted' => false,
        ]);
    }

    private function sanitize(mixed $assessment): ?array
    {
        if (! is_array($assessment) || ! is_array($assessment['scores'] ?? null)) {
            return null;
        }

        $scores = [];
        foreach (self::SCORE_FIELDS as $field) {
            $value = $assessment['scores'][$field] ?? null;
            if ($value === null || ! is_numeric($value)) {
                $scores[$field] = null;

                continue;
            }
            $scores[$field] = round(max(0, min(10, (float) $value)), 1);
        }

        if ($scores['overall'] === null) {
            $componentScores = array_filter(
                $scores,
                fn (mixed $score, string $field): bool => $field !== 'overall' && is_float($score),
                ARRAY_FILTER_USE_BOTH,
            );

            if ($componentScores === []) {
                return null;
            }

            $scores['overall'] = round(array_sum($componentScores) / count($componentScores), 1);
        }

        return [
            'scores' => $scores,
            'good' => $this->cleanText($assessment['good'] ?? '', 360),
            'improve' => $this->cleanText($assessment['improve'] ?? '', 420),
            'mistakes' => $this->cleanText($assessment['mistakes'] ?? '', 420),
            'betterAnswer' => $this->cleanText($assessment['betterAnswer'] ?? '', 900),
            'officerTip' => $this->cleanText($assessment['officerTip'] ?? '', 360),
            'scripted' => (bool) ($assessment['scripted'] ?? false),
        ];
    }

    private function cleanText(mixed $value, int $limit): string
    {
        return Str::limit(trim(strip_tags((string) $value)), $limit, '');
    }

    /**
     * Structured/JSON-schema mode normally returns strict JSON. Some smaller
     * local models occasionally place a literal newline/tab inside a JSON
     * string, though, which PHP correctly rejects. Escape only those control
     * bytes while inside strings and retry; structural whitespace is left intact.
     */
    private function decodeStructuredJson(string $content): array
    {
        $content = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content)) ?? $content);

        try {
            return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $firstException) {
            $repaired = $this->escapeJsonStringControlCharacters($content);

            if ($repaired === $content) {
                throw $firstException;
            }

            return json_decode($repaired, true, flags: JSON_THROW_ON_ERROR);
        }
    }

    private function escapeJsonStringControlCharacters(string $json): string
    {
        $repaired = '';
        $insideString = false;
        $escaped = false;
        $length = strlen($json);

        for ($index = 0; $index < $length; $index++) {
            $character = $json[$index];

            if (! $insideString) {
                $repaired .= $character;
                if ($character === '"') {
                    $insideString = true;
                }

                continue;
            }

            if ($escaped) {
                $repaired .= $character;
                $escaped = false;

                continue;
            }

            if ($character === '\\') {
                $repaired .= $character;
                $escaped = true;

                continue;
            }

            if ($character === '"') {
                $repaired .= $character;
                $insideString = false;

                continue;
            }

            $ordinal = ord($character);
            if ($ordinal < 0x20) {
                $repaired .= sprintf('\\u%04x', $ordinal);

                continue;
            }

            $repaired .= $character;
        }

        return $repaired;
    }

    private function logFailure(array $tier, string $operation, int $status, string $body): void
    {
        $decoded = json_decode($body, true);
        $detail = data_get($decoded, 'error.message') ?? data_get($decoded, 'error');

        Log::warning('Visa mock '.($tier['label'] ?? 'assessor').' '.$operation.' failed.', [
            'status' => $status,
            'detail' => is_string($detail) ? Str::limit($detail, 300, '') : null,
        ]);
    }
}
