<?php

namespace App\Http\Controllers;

use App\Services\VisaMockAssessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisaMockBatchAssessmentController extends Controller
{
    public function __invoke(Request $request, VisaMockAssessor $assessor): JsonResponse
    {
        $validated = $request->validate([
            'answers' => ['required', 'array', 'min:1', 'max:20'],
            'answers.*.question' => ['required', 'string', 'min:3', 'max:500'],
            'answers.*.answer' => ['required', 'string', 'min:3', 'max:5000'],
            'answers.*.category' => ['required', 'string', 'max:120'],
            'answers.*.mode' => ['required', 'in:text,video'],
            'answers.*.destination' => ['nullable', 'string', 'max:120'],
            'answers.*.metrics' => ['nullable', 'array'],
            'answers.*.metrics.wordCount' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'answers.*.metrics.durationSec' => ['nullable', 'numeric', 'min:0', 'max:900'],
            'answers.*.metrics.wpm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'answers.*.metrics.fillerCount' => ['nullable', 'integer', 'min:0', 'max:500'],
        ]);

        if (function_exists('set_time_limit')) {
            @set_time_limit($assessor->timeBudgetSeconds(true));
        }

        $results = $assessor->assessBatch($validated['answers']);

        if ($results === null) {
            return response()->json([
                'ok' => false,
                'retryable' => true,
                'message' => 'The assessment service could not complete the report. Please retry shortly.',
            ], 503);
        }

        return response()->json([
            'ok' => true,
            'engine' => 'ai-assessment-batch',
            'assessor' => $assessor->lastAssessorLabel(),
            'assessments' => $results,
        ]);
    }
}
