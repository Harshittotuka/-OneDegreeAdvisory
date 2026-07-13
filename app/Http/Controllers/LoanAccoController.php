<?php

namespace App\Http\Controllers;

use App\Support\ProfileSubmissionStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Loan & Acco — a self-contained Education-Loan + Student-Accommodation landing
 * page under the Student Hub (/loan-accommodation). It renders on the shared
 * site layout (layouts.app), so its navbar, footer and success/fail form popup
 * are identical to the rest of the site. Both enquiry forms POST here and are
 * recorded as leads in the shared ProfileSubmissionStore (source = "loan-acco"),
 * viewable at /admin/submissions → Loan & Acco.
 */
class LoanAccoController extends Controller
{
    public function __construct(private ProfileSubmissionStore $submissions)
    {
    }

    public function index(): View
    {
        return view('loan-acco.index', [
            'activeNav'       => 'new-tabs',
            'bodyClass'       => 'la-page-body',
            'pageTitle'       => 'Education Loan & Student Accommodation',
            'pageDescription' => 'Collateral & non-collateral education loans plus verified student housing — financed and arranged by one advisory team, from application to move-in.',
        ]);
    }

    /**
     * Capture a loan or accommodation enquiry. The forms POST here through the
     * site's shared AJAX handler (wireFormSubmit), which expects a JSON
     * {title, message} back to drive the success/fail popup. We map the raw
     * input names to human labels and store the lead in the same section →
     * question → answer shape the admin viewer / exports expect.
     */
    public function lead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'form'  => 'required|string|in:loan,accommodation',
            'name'  => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'required|string|max:40',
        ]);

        $name  = trim($validated['name']);
        $email = trim($validated['email']);
        $phone = trim($validated['phone']);
        $isLoan = $validated['form'] === 'loan';

        // Ordered [input name => display label] per form. The "…_other" free-text
        // companions are folded into their parent field below, not listed here.
        $map = $isLoan ? [
            'country'     => 'Country of study',
            'course'      => 'Course / program',
            'university'  => 'University',
            'loan_type'   => 'Preferred loan type',
            'loan_amount' => 'Loan amount required',
            'cibil'       => 'Co-applicant CIBIL score',
            'property'    => 'Property available for collateral',
        ] : [
            'country'   => 'Destination country',
            'city'      => 'City / university area',
            'move_date' => 'Preferred move-in date',
            'stay_type' => 'Accommodation type',
            'duration'  => 'Duration of stay',
            'budget'    => 'Monthly budget',
        ];

        $val = function (string $key) use ($request): string {
            $v = $request->input($key);

            return is_string($v) ? trim($v) : '';
        };

        $answers = [
            ['label' => 'Name',  'value' => [$name]],
            ['label' => 'Email', 'value' => [$email]],
            ['label' => 'Phone', 'value' => [$phone]],
        ];

        foreach ($map as $key => $label) {
            $value = $val($key);

            // Fold an "Other" selection into its free-text companion.
            if ($value === 'Other') {
                $other = $val($key.'_other');
                if ($other !== '') {
                    $value = $other;
                }
            }

            if ($value !== '') {
                $answers[] = ['label' => $label, 'value' => [Str::limit($value, 500, '')]];
            }
        }

        $eyebrow = $isLoan ? 'Education Loan' : 'Accommodation';

        $sections = [[
            'eyebrow' => $eyebrow,
            'title'   => $isLoan ? 'Education loan enquiry' : 'Accommodation enquiry',
            'answers' => $answers,
        ]];

        $this->submissions->add(
            'loan-acco',
            'Loan & Acco',
            $eyebrow, // shown in the admin "Degree" column as the enquiry type
            $sections,
            ['name' => $name, 'email' => $email, 'phone' => $phone],
        );

        return response()->json([
            'ok'      => true,
            'title'   => 'Request received',
            'message' => $isLoan
                ? 'A loan advisor will call you within 48–72 hours to walk through your eligibility and next steps.'
                : 'Our accommodation team will shortlist verified options and reach out within 48–72 hours.',
        ]);
    }
}
