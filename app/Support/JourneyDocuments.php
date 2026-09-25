<?php

namespace App\Support;

use App\Models\CrmJourneyApplication;
use App\Models\CrmJourneyDocument;
use App\Models\CrmJourneyPlan;
use App\Models\CrmLeadActivity;
use App\Models\CrmUser;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documents and essays on a journey plan, shared by the counsellor's planner
 * and the student portal so both follow the same rules:
 *
 *  - Both sides can upload files and write essays, and both see everything.
 *  - A student may change or remove only what they added; a counsellor any.
 *  - An essay moves Draft → Submitted (the student sends it for review) →
 *    Needs changes or Approved (the counsellor's call, with feedback).
 *    The student can keep editing until it is approved.
 *
 * Files sit on the private disk and are only served through the planner's
 * signed-in download routes, never from a public URL.
 */
class JourneyDocuments
{
    public const FILE_CATEGORIES = [
        'Transcript / mark sheet', 'Test score report', 'Passport / ID', 'CV / résumé', 'Recommendation letter',
        'Financial document', 'Offer / admission letter', 'Visa document', 'Other',
    ];

    public const ESSAY_CATEGORIES = ['Statement of Purpose', 'Personal statement', 'University essay', 'Scholarship essay', 'Other'];

    public const ESSAY_STATUSES = ['Draft', 'Submitted', 'Needs changes', 'Approved'];

    /** @return array<string, mixed> */
    public static function limits(): array
    {
        $c = config('journey.documents');

        return ['maxKb' => (int) $c['max_upload_kb'], 'extensions' => $c['extensions'], 'essayMaxChars' => (int) $c['essay_max_chars']];
    }

    /** Validation for a new document, file or essay. */
    public static function createRules(CrmJourneyPlan $plan): array
    {
        $c = config('journey.documents');

        return [
            'kind' => ['required', Rule::in(['file', 'essay'])],
            'title' => ['nullable', 'string', 'max:190'],
            'category' => ['required', 'string', Rule::in(array_merge(self::FILE_CATEGORIES, self::ESSAY_CATEGORIES))],
            'application_id' => ['nullable', 'integer', Rule::exists('crm_journey_applications', 'id')->where('plan_id', $plan->id)],
            'file' => ['required_if:kind,file', 'nullable', 'file', 'max:'.(int) $c['max_upload_kb'], 'mimes:'.implode(',', $c['extensions'])],
            'body' => ['nullable', 'string', 'max:'.(int) $c['essay_max_chars']],
        ];
    }

    /**
     * Store a new file or essay on the plan.
     *
     * @param  array<string, mixed>  $data  validated createRules() input
     */
    public static function create(CrmJourneyPlan $plan, array $data, ?UploadedFile $file, ?CrmUser $user): CrmJourneyDocument
    {
        if ($plan->documents()->count() >= (int) config('journey.documents.max_per_plan')) {
            throw ValidationException::withMessages(['file' => 'This plan already holds the most documents it can. Remove some you no longer need first.']);
        }

        $kind = $data['kind'];
        $categories = $kind === 'essay' ? self::ESSAY_CATEGORIES : self::FILE_CATEGORIES;
        if (! in_array($data['category'], $categories, true)) {
            throw ValidationException::withMessages(['category' => 'Choose a category from the list.']);
        }

        $attributes = [
            'kind' => $kind,
            'category' => $data['category'],
            'application_id' => $data['application_id'] ?? null,
            'by_student' => $user === null,
            'created_by' => $user?->id,
        ];

        if ($kind === 'file') {
            $name = Str::limit(trim($file->getClientOriginalName()), 180, '');
            $path = $file->storeAs(
                config('journey.documents.directory').'/'.$plan->id,
                Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension()),
                config('journey.documents.disk'),
            );
            $attributes += [
                'title' => trim((string) ($data['title'] ?? '')) ?: pathinfo($name, PATHINFO_FILENAME),
                'path' => $path,
                'original_name' => $name,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        } else {
            $attributes += [
                'title' => trim((string) ($data['title'] ?? '')) ?: $data['category'],
                'body' => (string) ($data['body'] ?? ''),
                'status' => 'Draft',
            ];
        }

        $document = $plan->documents()->create($attributes);

        if ($user === null && $kind === 'file') {
            self::log($plan, 'The student uploaded “'.$document->title.'” ('.$document->category.self::where($document).').');
        }

        return $document;
    }

    /**
     * Change a document. Students send title/category/university/body and
     * "submit"; counsellors can also send "review" with a status and feedback.
     *
     * @param  array<string, mixed>  $data
     */
    public static function update(CrmJourneyDocument $document, array $data, ?CrmUser $user): CrmJourneyDocument
    {
        $student = $user === null;
        if ($student && ! $document->by_student) {
            abort(403, 'Your counsellor added this one, so only they can change it.');
        }
        if ($student && $document->isEssay() && $document->status === 'Approved') {
            abort(403, 'This essay is approved. Ask your counsellor if it needs to change again.');
        }

        foreach (['title', 'category', 'application_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $document->{$field} = $field === 'title' ? (trim((string) $data[$field]) ?: $document->title) : $data[$field];
            }
        }
        if ($document->isEssay() && array_key_exists('body', $data)) {
            $document->body = (string) $data['body'];
            // Editing an essay that came back for changes starts a new draft.
            if ($student && $document->status === 'Needs changes') {
                $document->status = 'Draft';
            }
        }

        $submitted = false;
        if ($document->isEssay() && ! empty($data['submit'])) {
            if (trim((string) $document->body) === '') {
                throw ValidationException::withMessages(['body' => 'Write the essay before sending it for review.']);
            }
            $document->status = 'Submitted';
            $document->submitted_at = now();
            $submitted = true;
        }

        if (! $student && $document->isEssay() && array_key_exists('review_status', $data)) {
            $document->status = $data['review_status'];
            $document->feedback = trim((string) ($data['feedback'] ?? '')) ?: null;
            $document->feedback_by = $user->id;
            $document->feedback_at = now();
        }

        $document->save();

        if ($student && $submitted) {
            self::log($document->plan, 'The student sent the essay “'.$document->title.'”'.self::where($document).' for review ('.$document->wordCount().' words).');
        }

        return $document;
    }

    public static function delete(CrmJourneyDocument $document, ?CrmUser $user): void
    {
        if ($user === null) {
            abort_unless($document->by_student, 403, 'Your counsellor added this one, so only they can remove it.');
            abort_if($document->isEssay() && in_array($document->status, ['Submitted', 'Approved'], true), 403, 'This essay is with your counsellor. Ask them if it should be removed.');
        }

        DB::transaction(function () use ($document): void {
            $path = $document->path;
            $document->delete();
            if ($path) {
                Storage::disk(config('journey.documents.disk'))->delete($path);
            }
        });
    }

    public static function download(CrmJourneyDocument $document): StreamedResponse
    {
        abort_if($document->isEssay() || ! $document->path, 404);
        $disk = Storage::disk(config('journey.documents.disk'));
        abort_unless($disk->exists($document->path), 404);

        return $disk->download($document->path, $document->original_name ?: 'document', [
            'Content-Type' => $document->mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** The document if it belongs to the plan; a 404 otherwise, so ids from other plans reveal nothing. */
    public static function find(CrmJourneyPlan $plan, int|string $id): CrmJourneyDocument
    {
        return $plan->documents()->whereKey($id)->firstOrFail();
    }

    /** Validation for an update. Counsellors may also review essays. */
    public static function updateRules(CrmJourneyPlan $plan, bool $counsellor): array
    {
        $rules = [
            'title' => ['sometimes', 'nullable', 'string', 'max:190'],
            'category' => ['sometimes', 'string', Rule::in(array_merge(self::FILE_CATEGORIES, self::ESSAY_CATEGORIES))],
            'application_id' => ['sometimes', 'nullable', 'integer', Rule::exists('crm_journey_applications', 'id')->where('plan_id', $plan->id)],
            'body' => ['sometimes', 'nullable', 'string', 'max:'.(int) config('journey.documents.essay_max_chars')],
            'submit' => ['sometimes', 'boolean'],
        ];
        if ($counsellor) {
            $rules['review_status'] = ['sometimes', Rule::in(['Needs changes', 'Approved', 'Submitted', 'Draft'])];
            $rules['feedback'] = ['sometimes', 'nullable', 'string', 'max:5000'];
        }

        return $rules;
    }

    /**
     * One document as the page needs it.
     *
     * @param  'counsellor'|'partner'|'student'  $mode
     * @param  callable(CrmJourneyDocument): string  $fileUrl
     * @return array<string, mixed>
     */
    public static function toArray(CrmJourneyDocument $d, string $mode, callable $fileUrl): array
    {
        $student = $mode === 'student';
        $mine = $student ? $d->by_student : $mode === 'counsellor';
        $byName = $d->by_student ? ($student ? 'You' : 'Student') : ($d->creator?->name ?: 'Counsellor');

        return [
            'id' => $d->id,
            'kind' => $d->kind,
            'title' => $d->title,
            'category' => $d->category,
            'applicationId' => $d->application_id,
            'university' => $d->application?->university,
            'status' => $d->status,
            'feedback' => $d->feedback,
            'feedbackBy' => $d->reviewer?->name,
            'feedbackAt' => $d->feedback_at?->toIso8601String(),
            'submittedAt' => $d->submitted_at?->toIso8601String(),
            'body' => $d->isEssay() ? (string) $d->body : null,
            'words' => $d->isEssay() ? $d->wordCount() : null,
            'fileName' => $d->original_name,
            'mime' => $d->mime,
            'size' => $d->size,
            'url' => $d->isEssay() ? null : $fileUrl($d),
            'byStudent' => $d->by_student,
            'byName' => $byName,
            'createdAt' => $d->created_at?->toIso8601String(),
            'updatedAt' => $d->updated_at?->toIso8601String(),
            'canEdit' => $mode === 'counsellor' || ($mine && ! ($d->isEssay() && $d->status === 'Approved')),
            'canDelete' => $mode === 'counsellor' || ($mine && ! ($d->isEssay() && in_array($d->status, ['Submitted', 'Approved'], true))),
        ];
    }

    /** Read the validated "create" input off the request. */
    public static function createInput(Request $request, CrmJourneyPlan $plan): array
    {
        return $request->validate(self::createRules($plan), [
            'file.max' => 'That file is larger than '.round(config('journey.documents.max_upload_kb') / 1024).' MB. Upload a smaller copy.',
            'file.mimes' => 'Upload a PDF, Word document, or JPG/PNG image.',
            'file.required_if' => 'Choose a file to upload.',
        ]);
    }

    private static function where(CrmJourneyDocument $d): string
    {
        $university = $d->application_id ? CrmJourneyApplication::query()->whereKey($d->application_id)->value('university') : null;

        return $university ? ' for '.$university : '';
    }

    private static function log(CrmJourneyPlan $plan, string $body): void
    {
        CrmLeadActivity::query()->create([
            'crm_lead_id' => $plan->crm_lead_id, 'crm_user_id' => null, 'type' => 'journey_student', 'body' => $body,
        ]);
    }
}
