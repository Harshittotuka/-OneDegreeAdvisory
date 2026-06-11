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
use App\Support\StudyLocationContent;
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

    /**
     * Handle the Contact / Home enquiry form: notify the admissions team and
     * send the visitor a thank-you confirmation. Responds with JSON for the
     * AJAX submit, or redirects back with a flash message as a no-JS fallback.
     */
    public function submitContact(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'email'       => ['required', 'email', 'max:190'],
            'phone'       => ['required', 'string', 'max:40'],
            'city'        => ['nullable', 'string', 'max:120'],
            'destination' => ['nullable', 'string', 'max:120'],
            'level'       => ['required', 'string', 'max:120'],
            'message'     => ['nullable', 'string', 'max:5000'],
        ]);
        $data['consent'] = $request->boolean('consent');

        try {
            Mail::mailer(config('site.forms.contact.mailer'))
                ->to(config('site.forms.contact.to'))
                ->send(new ContactEnquiryMail($data));
        } catch (\Throwable $e) {
            report($e);

            return $this->formResponse($request, false,
                'We could not send your enquiry just now. Please email us directly at '.config('site.contact.email').'.',
                'Something went wrong');
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
            'Your enquiry is on its way to the One Degree Advisory team. Check your inbox for a confirmation email.',
            "Thank you, {$firstName}!");
    }

    /**
     * Handle the Careers application form: notify the careers mailbox and send
     * the applicant a thank-you confirmation.
     */
    public function submitCareer(Request $request): JsonResponse|RedirectResponse
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

        try {
            Mail::mailer(config('site.forms.careers.mailer'))
                ->to(config('site.forms.careers.to'))
                ->send(new CareerApplicationMail($data));
        } catch (\Throwable $e) {
            report($e);

            return $this->formResponse($request, false,
                'We could not submit your application just now. Please email us directly at Smita@onedegreeadvisory.com.',
                'Something went wrong');
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
            "Your application has reached our partners. Check your inbox for a confirmation — if there's a fit, we'll be in touch within ten working days.",
            "Thank you, {$firstName}!");
    }

    /**
     * Shared response shape for the public form handlers: JSON for AJAX
     * submissions (consumed by the success/error popup), or a redirect-back
     * with a flash message for no-JS clients.
     */
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

    public function blogPost(string $slug, BlogContent $blog): View
    {
        $post = $blog->forSlug($slug);
        abort_unless($post && (($post['visible'] ?? true) === true || session('cms_authenticated')), 404);

        return view('pages.blog-post', [
            'post'    => $post,
            'related' => $blog->related($slug, 4),
        ]);
    }

    public function testPreparation(): View
    {
        return view('pages.test-preparation');
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
