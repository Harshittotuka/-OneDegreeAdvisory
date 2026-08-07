<?php

namespace App\Http\Controllers;

use App\Services\RazorpayGateway;
use App\Services\WebsiteLeadManager;
use App\Support\CareerCounsellingStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Career Counselling — the counselling / assessment / guidance landing page
 * (/career-counselling), reached from the home hero's "Career Mentoring" button.
 *
 * Two ways in for a student, both of which land in the CRM:
 *   • "Book a consultation" / "Connect with us" → ::lead, recorded as a website
 *     lead (source = career-counselling, enquiry type = Career counselling);
 *   • a plan card's pay button → the shared /payments/order flow, priced
 *     server-side from CareerCounsellingStore and recorded as an enrolment
 *     (see PaymentBlockResolver + WebsiteLeadManager::capturePayment).
 */
class CareerCounsellingController extends Controller
{
    public function __construct(private WebsiteLeadManager $leads)
    {
    }

    public function index(CareerCounsellingStore $store, RazorpayGateway $razorpay): View
    {
        return view('pages.career-counselling', [
            'activeNav' => 'new-tabs',
            'bodyClass' => 'cc-page-body',
            'pageTitle' => 'Career Counselling & Career Assessments',
            'pageDescription' => 'Structured career counselling, psychometric career assessments and continuous career guidance for students in Class 8 to 12 and beyond — online and in person, across India.',
            'cc' => $store->get(),
            'payableOptions' => $store->payableOptions(),
            // The live pay button only renders when Razorpay keys are set;
            // otherwise the cards still show prices and route to an enquiry.
            'paymentEnabled' => $razorpay->configured(),
        ]);
    }

    /**
     * Capture a counselling enquiry. The form POSTs here through the site's
     * shared AJAX handler (wireFormSubmit), which expects a JSON {title, message}
     * back to drive the success/fail popup. Answers are stored in the same
     * section → question → answer shape the CRM viewer / exports expect.
     */
    public function lead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:40',
            'stage' => 'nullable|string|max:60',
            'message' => 'nullable|string|max:2000',
        ]);

        $name = trim($validated['name']);
        $email = trim($validated['email']);
        $phone = trim((string) ($validated['phone'] ?? ''));
        $stage = trim((string) ($validated['stage'] ?? ''));
        $message = trim((string) ($validated['message'] ?? ''));

        $answers = [
            ['label' => 'Name', 'value' => [$name]],
            ['label' => 'Email', 'value' => [$email]],
        ];

        if ($phone !== '') {
            $answers[] = ['label' => 'Phone', 'value' => [$phone]];
        }
        if ($stage !== '') {
            $answers[] = ['label' => 'Study level', 'value' => [Str::limit($stage, 60, '')]];
        }
        if ($message !== '') {
            $answers[] = ['label' => 'What they want help with', 'value' => [Str::limit($message, 1800, '')]];
        }

        $this->leads->capture(
            'career-counselling',
            'Career Counselling',
            $stage !== '' ? $stage : 'Career counselling',
            [[
                'eyebrow' => $stage !== '' ? $stage : 'Consultation',
                'title' => 'Career counselling consultation request',
                'answers' => $answers,
            ]],
            ['name' => $name, 'email' => $email, 'phone' => $phone],
        );

        return response()->json([
            'ok' => true,
            'title' => 'Request received',
            'message' => 'Thank you — a certified career counsellor will contact you within one working day to schedule your session.',
        ]);
    }
}
