<?php

namespace App\Http\Controllers;

use App\Support\ProfileSubmissionStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Loan & Acco — a self-contained Education-Loan + Student-Accommodation landing
 * page under the Student Hub (/loan-accommodation). The page shares the real
 * site navbar; its two enquiry forms POST here and are recorded as leads in the
 * shared ProfileSubmissionStore (source = "loan-acco"), so they show up at
 * /admin/submissions → Loan & Acco alongside profiler / career-library leads.
 */
class LoanAccoController extends Controller
{
    public function __construct(private ProfileSubmissionStore $submissions)
    {
    }

    public function index(): View
    {
        return view('loan-acco.index');
    }

    /**
     * Capture a loan or accommodation enquiry. The front-end sends a flat map of
     * already-labelled answer fields, so we store them as-is in the same
     * section → question → answer shape the admin viewer / exports expect.
     */
    public function lead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'form'   => 'required|string|in:loan,accommodation',
            'name'   => 'required|string|max:120',
            'email'  => 'required|email|max:190',
            'phone'  => 'required|string|max:40',
            'fields' => 'nullable|array',
            'fields.*' => 'nullable|string|max:500',
        ]);

        $name  = trim($validated['name']);
        $email = trim($validated['email']);
        $phone = trim($validated['phone']);

        $isLoan  = $validated['form'] === 'loan';
        $eyebrow = $isLoan ? 'Education Loan' : 'Accommodation';

        // Contact block first, then every supplied (already-labelled) field.
        $answers = [
            ['label' => 'Name', 'value' => [$name]],
            ['label' => 'Email', 'value' => [$email]],
            ['label' => 'Phone', 'value' => [$phone]],
        ];

        foreach (($validated['fields'] ?? []) as $label => $value) {
            $label = trim((string) $label);
            $value = trim((string) $value);
            if ($label === '' || $value === '') {
                continue;
            }
            $answers[] = ['label' => $label, 'value' => [$value]];
        }

        $sections = [[
            'eyebrow' => $eyebrow,
            'title'   => $isLoan ? 'Education loan enquiry' : 'Accommodation enquiry',
            'answers' => $answers,
        ]];

        $this->submissions->add(
            'loan-acco',
            'Loan & Acco',
            $eyebrow, // shown in the "Degree" column as the enquiry type
            $sections,
            ['name' => $name, 'email' => $email, 'phone' => $phone],
        );

        return response()->json(['ok' => true]);
    }
}
