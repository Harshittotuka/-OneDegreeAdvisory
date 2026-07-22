<?php

namespace App\Http\Controllers;

use App\Mail\CareerApplicationMail;
use App\Mail\CareerThankYouMail;
use App\Mail\ContactEnquiryMail;
use App\Mail\ContactThankYouMail;
use App\Support\AboutContent;
use App\Support\BlogContent;
use App\Support\HeroContent;
use App\Support\MbbsCountryContent;
use App\Services\WebsiteLeadManager;
use App\Support\StudyLocationContent;
use App\Support\TestPrepCompareStore;
use App\Services\RazorpayGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function home(Request $request, BlogContent $blog, HeroContent $hero): View
    {
        // The live CMS can preview unsaved hero edits via ?__hero_preview=1; the
        // data is stashed in the editor's own session and never published.
        $heroData = $request->boolean('__hero_preview') && $request->session()->has('home_hero_preview')
            ? $request->session()->get('home_hero_preview')
            : $hero->forDisplay();

        return view('pages.home', [
            'insights' => $blog->homeInsights(),
            'hero' => $heroData,
        ]);
    }

    public function about(AboutContent $about): View
    {
        return view('pages.about', [
            'sections' => $about->visible(),
        ]);
    }

    public function careers(): View
    {
        return view('pages.careers');
    }

    public function studyAbroad(): View
    {
        return view('pages.study-abroad');
    }

    // The Europe + brief pages are now CMS-built and rendered by
    // App\Http\Controllers\BriefPageController from App\Support\BriefPageStore.

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function privacyPolicy(): View
    {
        return view('pages.privacy-policy');
    }

    /**
     * Handle the Contact / Home enquiry form: notify the admissions team and
     * send the visitor a thank-you confirmation. Responds with JSON for the
     * AJAX submit, or redirects back with a flash message as a no-JS fallback.
     */
    public function submitContact(Request $request, WebsiteLeadManager $leads): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'email'       => ['required', 'email', 'max:190'],
            'phone'       => ['required', 'string', 'max:40'],
            'city'        => ['nullable', 'string', 'max:120'],
            'residence'   => ['nullable', 'string', 'max:120'],
            'destination' => ['nullable', 'string', 'max:120'],
            'level'       => ['required', 'string', 'max:120'],
            'message'     => ['nullable', 'string', 'max:5000'],
        ]);
        $data['consent'] = $request->boolean('consent');

        $contactAnswers = [];
        foreach (['city' => 'City', 'residence' => 'Resident country', 'destination' => 'Preferred destination', 'level' => 'Academic level', 'message' => 'Message'] as $key => $label) {
            $value = trim((string) ($data[$key] ?? ''));
            if ($value !== '') $contactAnswers[] = ['label' => $label, 'value' => [Str::limit($value, 1800, '')]];
        }
        $leads->capture('contact', 'Contact / Profile Review', $data['level'] ?? null, [[
            'eyebrow' => 'Website enquiry', 'title' => 'Contact request', 'answers' => $contactAnswers,
        ]], ['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone']]);

        try {
            Mail::mailer(config('site.forms.contact.mailer'))
                ->to(config('site.forms.contact.to'))
                ->send(new ContactEnquiryMail($data));
        } catch (\Throwable $e) {
            report($e);
        }

        // Confirmation to the visitor is best-effort — its failure never blocks success.
        try {
            Mail::mailer(config('site.forms.contact.mailer'))
                ->to($data['email'])
                ->send(new ContactThankYouMail($data));
        } catch (\Throwable $e) {
            report($e);
        }

        $firstName = Str::of($data['name'])->trim()->explode(' ')->first() ?: 'there';

        return $this->formResponse($request, true,
            'Your enquiry is safely recorded and the One Degree Advisory team will follow up with you shortly.',
            "Thank you, {$firstName}!");
    }

    /**
     * Handle the Careers application form: notify the careers mailbox and send
     * the applicant a thank-you confirmation.
     */
    public function submitCareer(Request $request, WebsiteLeadManager $leads): JsonResponse|RedirectResponse
    {
        // A resume can arrive as an uploaded file OR a link — at least one is
        // required. The PHP upload ceiling is 2 MB, so the file rule matches.
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'email'       => ['required', 'email', 'max:190'],
            'phone'       => ['required', 'string', 'max:40'],
            'linkedin'    => ['nullable', 'url', 'max:255'],
            'role'        => ['required', 'string', 'max:160'],
            'experience'  => ['required', 'string', 'max:60'],
            'message'     => ['required', 'string', 'max:5000'],
            'resume_link' => ['nullable', 'url', 'max:255', 'required_without:resume_file'],
            'resume_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:2048', 'required_without:resume_link'],
            'consent'     => ['accepted'],
        ], [
            'resume_link.required_without' => 'Please attach your resume or paste a link to it.',
            'resume_file.required_without' => 'Please attach your resume or paste a link to it.',
        ]);

        // Stash the uploaded resume so the mailable can attach it. Stored on the
        // local disk; the path/name/mime travel with the form data.
        $resume = null;
        if ($request->hasFile('resume_file')) {
            $file = $request->file('resume_file');
            $resume = [
                'path' => $file->store('career-resumes', 'local'),
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
            ];
        }

        $data = [
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'phone'       => $validated['phone'],
            'linkedin'    => $validated['linkedin'] ?? null,
            'role'        => $validated['role'],
            'experience'  => $validated['experience'],
            'message'     => $validated['message'],
            'resume_link' => $validated['resume_link'] ?? null,
            'resume'      => $resume,
            'consent'     => true,
        ];

        $careerAnswers = [];
        foreach (['role' => 'Role applied for', 'experience' => 'Experience', 'linkedin' => 'LinkedIn', 'resume_link' => 'Resume link', 'message' => 'Application message'] as $key => $label) {
            $value = trim((string) ($data[$key] ?? ''));
            if ($value !== '') $careerAnswers[] = ['label' => $label, 'value' => [Str::limit($value, 1800, '')]];
        }
        $leads->capture('careers', 'Careers Application', $data['role'], [[
            'eyebrow' => 'Careers', 'title' => 'Job application', 'answers' => $careerAnswers,
        ]], ['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone']]);

        try {
            Mail::mailer(config('site.forms.careers.mailer'))
                ->to(config('site.forms.careers.to'))
                ->send(new CareerApplicationMail($data));
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            Mail::mailer(config('site.forms.careers.mailer'))
                ->to($data['email'])
                ->send(new CareerThankYouMail($data));
        } catch (\Throwable $e) {
            report($e);
        }

        $firstName = Str::of($data['name'])->trim()->explode(' ')->first() ?: 'there';

        return $this->formResponse($request, true,
            "Your application is safely recorded. If there's a fit, we'll be in touch within ten working days.",
            "Thank you, {$firstName}!");
    }

    /**
     * Shared response shape for the public form handlers: JSON for AJAX
     * submissions (consumed by the success/error popup), or a redirect-back
     * with a flash message for no-JS clients.
     */
    /**
     * Store an email from the blog newsletter sign-up forms ("Stay Current On
     * College Admissions" and "Stay in the loop"). Duplicate addresses are
     * ignored; the visitor sees the same friendly confirmation either way.
     */
    public function subscribeNewsletter(Request $request, WebsiteLeadManager $leads): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'email'  => ['required', 'email', 'max:190'],
            'source' => ['nullable', 'string', 'max:80'],
        ]);

        $leads->captureNewsletter($data['email'], $data['source'] ?? 'Blog newsletter');

        return $this->formResponse(
            $request,
            true,
            "We'll send the next brief straight to your inbox.",
            "You're on the list!"
        );
    }

    private function formResponse(Request $request, bool $ok, string $message, ?string $title = null): JsonResponse|RedirectResponse
    {
        $title ??= $ok ? 'Thank you!' : 'Something went wrong';

        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'title' => $title, 'message' => $message], $ok ? 200 : 422);
        }

        return back()
            ->with('form_ok', $ok)
            ->with('form_status', trim($title.' '.$message));
    }

    public function blogIndex(Request $request, BlogContent $blog): View|RedirectResponse
    {
        $perPage = 9;
        $requestedPage = max(1, (int) $request->query('page', 1));
        $page = $requestedPage;

        // Only show posts whose visibility is on (default true for legacy posts).
        $allPosts = array_values(array_filter(
            $blog->all(),
            fn (array $p) => ($p['visible'] ?? true) === true
        ));

        // Pull out the pinned/featured post so it stays on top of every page
        // and never gets pushed down by pagination.
        $featured = null;
        foreach ($allPosts as $i => $candidate) {
            if (! empty($candidate['featured'])) {
                $featured = $candidate;
                unset($allPosts[$i]);
                break;
            }
        }
        $allPosts = array_values($allPosts);

        $totalPages = max(1, (int) ceil(count($allPosts) / $perPage));

        if ($requestedPage > $totalPages) {
            return redirect()->to($totalPages === 1 ? route('blog.index') : route('blog.index', ['page' => $totalPages]), 301);
        }

        $page       = min($page, $totalPages);
        $posts      = array_slice($allPosts, ($page - 1) * $perPage, $perPage);

        // The featured post renders as the big hero card at the top (template
        // treats posts[0] as featured). Pinning it here keeps it first on page 1+.
        if ($featured) {
            array_unshift($posts, $featured);
        }

        return view('pages.blog-index', [
            'posts'      => $posts,
            'page'       => $page,
            'totalPages' => $totalPages,
        ]);
    }

    public function blogPost(string $slug, BlogContent $blog): View|RedirectResponse
    {
        $post = $blog->forSlug($slug);
        abort_unless($post && (($post['visible'] ?? true) === true || session('cms_authenticated')), 404);

        // A "redirect" post has no article of its own — send visitors to its target.
        $link = trim((string) ($post['link_url'] ?? ''));
        if ($link !== '') {
            return redirect()->to($link, 301);
        }

        return view('pages.blog-post', [
            'post'    => $post,
            'related' => $blog->related($slug, 4),
        ]);
    }

    public function testPreparation(TestPrepCompareStore $compare, RazorpayGateway $razorpay): View
    {
        return view('pages.test-preparation', [
            'compare' => $compare->get(),
            // The live "Pay securely" button only renders when Razorpay keys are
            // set; otherwise the section still shows prices but routes to enquiry.
            'paymentEnabled' => $razorpay->configured(),
        ]);
    }

    public function admissionsCounselling(): View
    {
        return view('pages.admissions-counselling');
    }

    public function studentServices(): View
    {
        return view('pages.student-services');
    }

    public function undergraduate(): View
    {
        return view('pages.undergraduate');
    }

    public function postgraduate(): View
    {
        return view('pages.postgraduate');
    }

    public function llb(): View
    {
        return view('pages.llb');
    }

    public function mba(): View
    {
        return view('pages.mba');
    }

    public function doctoral(): View
    {
        return view('pages.doctoral');
    }

    /**
     * Visa — a self-contained Student Hub landing page with a free
     * visa-eligibility pre-check. It renders on the shared site layout so the
     * navbar/footer match the rest of the site; there is no server-side lead
     * capture (the advisor CTA links to /contact and the checker result opens a
     * WhatsApp/email popup), so no controller logic beyond serving the view.
     */
    public function visa(): View
    {
        return view('pages.visa', [
            'activeNav'       => 'new-tabs',
            'bodyClass'       => 'visa-page-body',
            'pageTitle'       => 'Student Visa Guidance & Free Eligibility Check',
            'pageDescription' => 'Expert student visa guidance — a free 60-second eligibility pre-check, refusal analysis, mock interviews, and end-to-end filing support for any destination and any university, ranked or not.',
            'mainId'          => 'main',
        ]);
    }

    /**
     * AI Visa Mock Interview — a browser-first mock-interview tool under the
     * Student Hub. Video mode provides a live practice preview while answer
     * transcripts are reviewed by our locally hosted Ollama service through
     * a separate throttled endpoint.
     */
    public function visaMock(): View
    {
        return view('pages.visa-mock-interview', [
            'activeNav'       => 'new-tabs',
            'bodyClass'       => 'vmi-page-body',
            'pageTitle'       => 'AI Visa Mock Interview — Free Practice & Feedback',
            'pageDescription' => 'Practise your student-visa interview with an AI assessor: real embassy-style questions, video or text answers, and a detailed readiness report. Free 10-question round.',
            'mainId'          => 'main',
        ]);
    }

    /**
     * Capture a "unlock the full interview" lead from the mock-interview page.
     * The popup POSTs here through a small fetch handler that expects a JSON
     * {ok, title, message} back. The lead is stored in the shared
     * CRM (source = "visa-mock") in the same section → question → answer
     * shape used by CRM card, table and export views.
     */
    public function visaMockLead(Request $request, WebsiteLeadManager $leads): JsonResponse
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'required|string|max:40',
            // Optional context about the practice round they were unlocking.
            'destination' => 'nullable|string|max:120',
            'level'       => 'nullable|string|max:120',
            'plan'        => 'nullable|string|max:120',
        ]);

        $name  = trim($validated['name']);
        $email = trim($validated['email']);
        $phone = trim($validated['phone']);

        $answers = [
            ['label' => 'Name',  'value' => [$name]],
            ['label' => 'Email', 'value' => [$email]],
            ['label' => 'Phone', 'value' => [$phone]],
        ];

        foreach ([
            'destination' => 'Destination country',
            'level'       => 'Study level',
            'plan'        => 'Interview length requested',
        ] as $key => $label) {
            $value = trim((string) ($validated[$key] ?? ''));
            if ($value !== '') {
                $answers[] = ['label' => $label, 'value' => [Str::limit($value, 200, '')]];
            }
        }

        $sections = [[
            'eyebrow' => 'Visa Mock Interview',
            'title'   => 'Consulting / callback request',
            'answers' => $answers,
        ]];

        $leads->capture(
            'visa-mock',
            'Visa Mock Interview',
            null,
            $sections,
            ['name' => $name, 'email' => $email, 'phone' => $phone],
        );

        return response()->json([
            'ok'      => true,
            'title'   => "Thanks — we've got your details",
            'message' => 'Our visa team will reach out to you shortly with proper consulting and the next steps to prepare for your interview. In the meantime, you can keep practising the free round.',
        ]);
    }

    public function mbbsStudent(MbbsCountryContent $content): View
    {
        return view('pages.mbbs-student', [
            'mbbsCountries' => $content->countries(),
        ]);
    }

    public function country(string $country, StudyLocationContent $content): View
    {
        abort_unless($content->isVisible($country), 404);

        $studyContent = $content->forSlug($country);

        abort_unless($studyContent['page'] ?? null, 404);

        return view('countries.destination', [
            'destination' => $studyContent['destination'] ?? [],
            'studyContent' => $studyContent,
        ]);
    }

    public function mbbsCountry(string $country, MbbsCountryContent $content): View
    {
        abort_unless($content->isVisible($country), 404);

        $mbbsContent = $content->forSlug($country);
        abort_unless($mbbsContent['page'] ?? null, 404);

        return view('mbbs.country', [
            'countrySlug' => $country,
            'countryMeta' => $mbbsContent['country'] ?? [],
            'mbbsContent' => $mbbsContent,
        ]);
    }
}
