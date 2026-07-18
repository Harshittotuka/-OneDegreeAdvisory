<?php

namespace App\Http\Controllers;

use App\Services\WebsiteLeadManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Statement of Purpose — a self-contained SOP / admissions-writing studio
 * landing page under the Student Hub (/statement-of-purpose). It renders on the
 * shared site layout (layouts.app), so its navbar, footer and success/fail form
 * popup match the rest of the site. The "book a strategy call" form POSTs here
 * and is recorded as a CRM website lead (source = "sop").
 */
class SopController extends Controller
{
    public function __construct(private WebsiteLeadManager $leads)
    {
    }

    public function index(): View
    {
        return view('pages.sop', [
            'activeNav'       => 'new-tabs',
            'bodyClass'       => 'sop-page-body',
            'pageTitle'       => 'Statement of Purpose & Admissions Writing Studio',
            'pageDescription' => 'Human-written Statement of Purpose, visa SOPs, resumes, letters of recommendation and scholarship essays for students applying abroad — one advisor, one consistent story, from first line to final submission.',
        ]);
    }

    /**
     * Capture a "book your strategy call" enquiry. The form POSTs here through the
     * site's shared AJAX handler (wireFormSubmit), which expects a JSON
     * {title, message} back to drive the success/fail popup. We map the raw input
     * names to human labels and store the lead in the same section → question →
     * answer shape the admin viewer / exports expect.
     */
    public function lead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:120',
            'email'   => 'required|email|max:190',
            'service' => 'nullable|string|max:160',
            'message' => 'required|string|max:2000',
        ]);

        $name    = trim($validated['name']);
        $email   = trim($validated['email']);
        $service = trim($validated['service'] ?? '');
        $message = trim($validated['message']);

        $answers = [
            ['label' => 'Name',  'value' => [$name]],
            ['label' => 'Email', 'value' => [$email]],
        ];

        if ($service !== '') {
            $answers[] = ['label' => 'Service needed', 'value' => [Str::limit($service, 200, '')]];
        }

        $answers[] = ['label' => 'Target program / message', 'value' => [Str::limit($message, 1800, '')]];

        $eyebrow = $service !== '' ? $service : 'Strategy call';

        $sections = [[
            'eyebrow' => $eyebrow,
            'title'   => 'Strategy call request',
            'answers' => $answers,
        ]];

        $this->leads->capture(
            'sop',
            'Statement of Purpose',
            $eyebrow, // shown in the admin "Degree" column as the requested service
            $sections,
            ['name' => $name, 'email' => $email, 'phone' => ''],
        );

        return response()->json([
            'ok'      => true,
            'title'   => 'Request received',
            'message' => 'Thank you — your request has been queued. An advisor will email you within one business day with next steps and a fit assessment.',
        ]);
    }
}
