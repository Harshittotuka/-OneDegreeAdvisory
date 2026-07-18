<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisaMockInterviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Default every test to the local-only path so a developer's real
        // GROQ_API_KEY in .env cannot alter which tier the chain uses. The
        // Groq-specific tests opt back in by setting the key explicitly.
        config()->set('services.visa_mock_ai.groq.key', null);
    }

    public function test_interview_page_exposes_the_ai_batch_endpoint(): void
    {
        $this->get(route('visa-mock'))
            ->assertOk()
            ->assertSee('assessBatchUrl:', false)
            ->assertSee('visa-mock-interview\\/assess-batch', false)
            ->assertSee('600000', false)
            ->assertDontSee('assessAnswerLocally', false)
            ->assertDontSee('private browser assessment', false)
            ->assertSee('assessmentEngine:"pending-ai"', false)
            ->assertSee('await assessSavedAnswers()', false)
            ->assertSee('requestAiBatchAssessment', false)
            ->assertSee('vmi-analysis-time', false)
            ->assertSee('startAnalysisEstimate', false)
            ->assertSee('Approximately ', false)
            ->assertDontSee('Body language assessment')
            ->assertDontSee('visa-mock-vision.js')
            ->assertDontSee('MediaPipe')
            ->assertDontSee('VMI_VISION', false)
            ->assertDontSee('Download certificate')
            ->assertDontSee('downloadCertificate', false)
            ->assertDontSee('private local AI', false)
            ->assertDontSee('private AI', false)
            ->assertSee('Video + voice')
            ->assertSee('Text only')
            ->assertSee('Answer quality distribution')
            ->assertSee('Executive insights')
            ->assertSee('Knowledge and answer quality')
            ->assertSee('Performance by interview category')
            ->assertSee('Complete answer review')
            ->assertSee('report-stats', false)
            ->assertSee('buildReportAnalytics', false)
            ->assertSee('renderAnswerReview', false)
            ->assertDontSee('id="report-breakdown"', false);
    }

    public function test_all_answers_are_assessed_in_one_ordered_ollama_request(): void
    {
        config()->set('services.visa_mock_ai.enabled', true);
        config()->set('services.visa_mock_ai.url', 'http://ollama.test:11434');
        config()->set('services.visa_mock_ai.model', 'qwen3:4b-instruct');

        $first = $this->compactBatchFixture(8.4, 'Funding answer was specific.');
        $second = $this->compactBatchFixture(7.6, 'Course answer connected study and career.');

        Http::fake([
            'ollama.test:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode(['assessments' => [$first, $second]], JSON_THROW_ON_ERROR),
                ],
            ]),
        ]);

        $secondAnswer = $this->validPayload();
        $secondAnswer['question'] = 'Why did you choose this course?';
        $secondAnswer['answer'] = 'The modules build on my computer science degree and support my data career plan.';
        $secondAnswer['category'] = 'University & Course Related';

        $this->postJson(route('visa-mock.assess-batch'), [
            'answers' => [$this->validPayload(), $secondAnswer],
        ])
            ->assertOk()
            ->assertJsonPath('engine', 'ai-assessment-batch')
            ->assertJsonCount(2, 'assessments')
            ->assertJsonPath('assessments.0.scores.overall', 8.4)
            ->assertJsonPath('assessments.1.scores.overall', 7.6);

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'http://ollama.test:11434/api/chat'
                && $request['model'] === 'qwen3:4b-instruct'
                && $request['stream'] === false
                && ($request['format']['properties']['assessments']['type'] ?? null) === 'array'
                && ($request['format']['properties']['assessments']['minItems'] ?? null) === 2;
        });
    }

    public function test_batch_assessment_repairs_literal_control_characters_from_local_model(): void
    {
        config()->set('services.visa_mock_ai.enabled', true);
        config()->set('services.visa_mock_ai.url', 'http://ollama.test:11434');
        config()->set('services.visa_mock_ai.model', 'qwen3:4b-instruct');

        $content = json_encode([
            'assessments' => [$this->compactBatchFixture(8.4, 'Funding answer was specific and clear.')],
        ], JSON_THROW_ON_ERROR);
        $content = str_replace('specific and clear.', "specific\nand clear.", $content);

        Http::fake([
            'ollama.test:11434/api/chat' => Http::response([
                'message' => ['content' => $content],
            ]),
        ]);

        $this->postJson(route('visa-mock.assess-batch'), [
            'answers' => [$this->validPayload()],
        ])
            ->assertOk()
            ->assertJsonPath('assessments.0.good', "Funding answer was specific\nand clear.");
    }

    public function test_batch_assessment_derives_missing_overall_without_discarding_report(): void
    {
        config()->set('services.visa_mock_ai.enabled', true);
        config()->set('services.visa_mock_ai.url', 'http://ollama.test:11434');

        $fixture = $this->compactBatchFixture(8.4, 'Funding answer was specific.');
        $fixture['s'][10] = null;

        Http::fake([
            'ollama.test:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode(['assessments' => [$fixture]], JSON_THROW_ON_ERROR),
                ],
            ]),
        ]);

        $this->postJson(route('visa-mock.assess-batch'), [
            'answers' => [$this->validPayload()],
        ])
            ->assertOk()
            ->assertJsonPath('assessments.0.scores.overall', 8.2);
    }

    public function test_answer_can_be_assessed_by_the_local_structured_ai(): void
    {
        config()->set('services.visa_mock_ai.enabled', true);
        config()->set('services.visa_mock_ai.url', 'http://ollama.test:11434');
        config()->set('services.visa_mock_ai.model', 'qwen3:4b-instruct');

        Http::fake([
            'ollama.test:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'scores' => [
                            'language' => 8.2,
                            'grammar' => 8.5,
                            'confidence' => 7.6,
                            'clarity' => 8.1,
                            'crispness' => 7.9,
                            'relevance' => 9.1,
                            'courseKnowledge' => null,
                            'universityKnowledge' => null,
                            'countryKnowledge' => null,
                            'financialAwareness' => 8.8,
                            'overall' => 8.4,
                        ],
                        'good' => 'You named the sponsor and the funding source.',
                        'improve' => 'Add the exact tuition and living-cost figures.',
                        'mistakes' => 'The amount available was not stated.',
                        'betterAnswer' => 'My [relationship] will sponsor me using [source], with [amount] available.',
                        'officerTip' => 'Keep the figures consistent with your documents.',
                        'scripted' => false,
                    ], JSON_THROW_ON_ERROR),
                ],
            ]),
        ]);

        $this->postJson(route('visa-mock.assess'), $this->validPayload())
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('engine', 'ai-assessment')
            ->assertJsonPath('assessment.scores.relevance', 9.1)
            ->assertJsonPath('assessment.good', 'You named the sponsor and the funding source.');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'http://ollama.test:11434/api/chat'
                && $request['model'] === 'qwen3:4b-instruct'
                && $request['stream'] === false
                && ($request['format']['type'] ?? null) === 'object';
        });
    }

    public function test_batch_uses_groq_primary_when_a_key_is_configured(): void
    {
        config()->set('services.visa_mock_ai.enabled', true);
        config()->set('services.visa_mock_ai.groq.key', 'gsk_test');
        config()->set('services.visa_mock_ai.groq.url', 'https://groq.test/openai/v1');
        config()->set('services.visa_mock_ai.groq.primary_model', 'llama-3.3-70b-versatile');
        config()->set('services.visa_mock_ai.url', 'http://ollama.test:11434');

        $fixture = $this->compactBatchFixture(8.4, 'Funding answer was specific.');

        Http::fake([
            'groq.test/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode(['assessments' => [$fixture]], JSON_THROW_ON_ERROR)]],
                ],
            ]),
        ]);

        $this->postJson(route('visa-mock.assess-batch'), ['answers' => [$this->validPayload()]])
            ->assertOk()
            ->assertJsonPath('engine', 'ai-assessment-batch')
            ->assertJsonPath('assessor', 'Groq llama-3.3-70b-versatile')
            ->assertJsonPath('assessments.0.scores.overall', 8.4);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://groq.test/openai/v1/chat/completions'
                && $request['model'] === 'llama-3.3-70b-versatile'
                && ($request['response_format']['type'] ?? null) === 'json_object'
                // The exact shape is described in-prompt for json_object mode.
                && str_contains($request['messages'][0]['content'] ?? '', '"assessments"');
        });
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'ollama.test'));
    }

    public function test_batch_falls_back_through_the_chain_to_local_ollama(): void
    {
        config()->set('services.visa_mock_ai.enabled', true);
        config()->set('services.visa_mock_ai.groq.key', 'gsk_test');
        config()->set('services.visa_mock_ai.groq.url', 'https://groq.test/openai/v1');
        config()->set('services.visa_mock_ai.url', 'http://ollama.test:11434');
        config()->set('services.visa_mock_ai.model', 'qwen2.5:1.5b');

        $fixture = $this->compactBatchFixture(7.6, 'Course answer connected study and career.');

        Http::fake([
            // Both Groq tiers reject (e.g. rate-limited) → fall through to local.
            'groq.test/openai/v1/chat/completions' => Http::response(['error' => ['message' => 'rate limit reached']], 429),
            'ollama.test:11434/api/chat' => Http::response([
                'message' => ['content' => json_encode(['assessments' => [$fixture]], JSON_THROW_ON_ERROR)],
            ]),
        ]);

        $this->postJson(route('visa-mock.assess-batch'), ['answers' => [$this->validPayload()]])
            ->assertOk()
            ->assertJsonPath('assessor', 'On-server qwen2.5:1.5b')
            ->assertJsonPath('assessments.0.scores.overall', 7.6);

        // Groq 70B + Groq 8B both tried, then the local Ollama tier answered.
        Http::assertSentCount(3);
    }

    public function test_interview_blocks_assessment_when_local_ai_is_disabled(): void
    {
        config()->set('services.visa_mock_ai.enabled', false);
        Http::preventStrayRequests();

        $this->postJson(route('visa-mock.assess'), $this->validPayload())
            ->assertStatus(503)
            ->assertJson([
                'ok' => false,
                'retryable' => true,
            ])
            ->assertJsonMissingPath('fallback');
    }

    public function test_assessment_rejects_invalid_or_oversized_answers(): void
    {
        $payload = $this->validPayload();
        $payload['answer'] = '';
        $payload['mode'] = 'camera';

        $this->postJson(route('visa-mock.assess'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answer', 'mode']);
    }

    private function validPayload(): array
    {
        return [
            'question' => 'Who is sponsoring your education?',
            'answer' => 'My father is sponsoring my education through family savings and his business income.',
            'category' => 'Financial Questions',
            'mode' => 'video',
            'metrics' => [
                'wordCount' => 13,
                'durationSec' => 9,
                'wpm' => 87,
                'fillerCount' => 0,
            ],
        ];
    }

    private function assessmentFixture(float $overall, string $good): array
    {
        return [
            'scores' => [
                'language' => 8.2,
                'grammar' => 8.5,
                'confidence' => 7.6,
                'clarity' => 8.1,
                'crispness' => 7.9,
                'relevance' => 9.1,
                'courseKnowledge' => null,
                'universityKnowledge' => null,
                'countryKnowledge' => null,
                'financialAwareness' => null,
                'overall' => $overall,
            ],
            'good' => $good,
            'improve' => 'Add one more verifiable detail.',
            'mistakes' => '',
            'betterAnswer' => 'A concise improved answer using only supplied facts.',
            'officerTip' => 'Keep the answer direct and consistent.',
            'scripted' => false,
        ];
    }

    private function compactBatchFixture(float $overall, string $good): array
    {
        return [
            's' => [8.2, 8.5, 7.6, 8.1, 7.9, 9.1, null, null, null, null, $overall],
            'g' => $good,
            'i' => 'Add one more verifiable detail.',
            'm' => '',
            'b' => 'A concise improved answer using only supplied facts.',
        ];
    }
}
