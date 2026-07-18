<?php

namespace App\Http\Controllers;

use App\Services\VisaMockAssessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisaMockAssessmentController extends Controller
{
    public function __invoke(Request $request, VisaMockAssessor $assessor): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:500'],
            'answer' => ['required', 'string', 'min:3', 'max:5000'],
            'category' => ['required', 'string', 'max:120'],
            'mode' => ['required', 'in:text,video'],
            'destination' => ['nullable', 'string', 'max:120'],
            'metrics' => ['nullable', 'array'],
            'metrics.wordCount' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'metrics.durationSec' => ['nullable', 'numeric', 'min:0', 'max:900'],
            'metrics.wpm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'metrics.fillerCount' => ['nullable', 'integer', 'min:0', 'max:500'],
        ]);

        // Model inference can exceed PHP's common 30-second web limit during
        // model loading, and the request may fall through several tiers. Align
        // PHP with the whole chain's worst-case budget so it finishes rather
        // than being killed mid-fallback.
        if (function_exists('set_time_limit')) {
            @set_time_limit($assessor->timeBudgetSeconds(false));
        }

        $result = $assessor->assess($validated);

        if ($result === null) {
            return response()->json([
                'ok' => false,
                'retryable' => true,
                'message' => 'The assessment service is temporarily unavailable. Please retry shortly.',
            ], 503);
        }

        return response()->json([
            'ok' => true,
            'engine' => 'ai-assessment',
            'assessor' => $assessor->lastAssessorLabel(),
            'assessment' => $result,
        ]);
    }
}
