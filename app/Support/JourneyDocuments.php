<?php

namespace App\Support;

use App\Models\CrmJourneyApplication;
use App\Models\CrmJourneyDocument;
use App\Models\CrmJourneyDocumentEdit;
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
 *  - Every document carries its own edit history: the planner writes an entry
 *    whenever the document moves (a draft saved, sent for review, reviewed),
 *    and either side can add one by hand for a change made outside the
 *    planner, with a file if they like. The version number moves only when
 *    the content does.
 *  - A new file logged against an uploaded file becomes its current version;
 *    every earlier copy stays on its own history entry, still downloadable.
 *
 * Files sit on the private disk and are only served through the planner's
 * signed-in download routes, never from a public URL.
 */
class JourneyDocuments
{
    public const FILE_CATEGORIES = [
        'Transcript / mark sheet', 'Test score report', 'Passport / ID', 'CV / résumé', 'Recommendation letter',
        'Financial document', 'Offer / admission letter', 'Visa document', 'Student declaration form', 'Other',
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
            'version' => 1,
            'by_student' => $user === null,
            'created_by' => $user?->id,
        ];

        $stored = [];
        if ($kind === 'file') {
            $stored = self::storeFile($plan, $file);
            $attributes += $stored + [
                'title' => trim((string) ($data['title'] ?? '')) ?: pathinfo($stored['original_name'], PATHINFO_FILENAME),
            ];
        } else {
            $attributes += [
                'title' => trim((string) ($data['title'] ?? '')) ?: $data['category'],
                'body' => (string) ($data['body'] ?? ''),
                'status' => 'Draft',
            ];
        }

        $document = $plan->documents()->create($attributes);
        // The first copy of a file sits on the first history entry, like every later one.
        self::recordEdit($document, $kind === 'file' ? 'Uploaded “'.$document->original_name.'”' : 'Started the draft', $user, file: $stored);
        JourneyLog::record($plan, $user, 'Documents', $document->title, ($kind === 'file' ? 'Uploaded' : 'Draft started').' · '.$document->category);
        if ($kind === 'file') {
            self::advanceUploadTask($plan, $document, $user);
        }

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

        $wasTitle = $document->title;
        foreach (['title', 'category', 'application_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $document->{$field} = $field === 'title' ? (trim((string) $data[$field]) ?: $document->title) : $data[$field];
            }
        }
        $detailsMoved = $document->isDirty(['title', 'category', 'application_id']);
        $bodyChanged = false;
        if ($document->isEssay() && array_key_exists('body', $data)) {
            $bodyChanged = (string) $data['body'] !== (string) $document->body;
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

        // The history: the content moving earns a new version, the rest is a
        // note on the version that is there.
        if ($bodyChanged) {
            self::recordEdit($document, $student ? 'Draft rewritten' : 'Edited the draft', $user, newVersion: true);
        }
        if ($submitted) {
            self::recordEdit($document, 'Sent for review ('.$document->wordCount().' words)', $user);
        }
        if (! $student && array_key_exists('review_status', $data)) {
            self::recordEdit($document, match ($document->status) {
                'Approved' => 'Approved',
                'Needs changes' => 'Sent back for changes',
                default => 'Review status set to '.$document->status,
            }, $user);
        }

        if ($student && $submitted) {
            self::log($document->plan, 'The student sent the essay “'.$document->title.'”'.self::where($document).' for review ('.$document->wordCount().' words).');
        }

        JourneyLog::record($document->plan, $user, 'Documents', $document->title, implode(' · ', array_filter([
            $detailsMoved ? ($document->title !== $wasTitle ? 'Renamed from “'.$wasTitle.'”' : 'Details updated') : null,
            $bodyChanged ? 'Draft edited' : null,
            $submitted ? 'Sent for review' : null,
            ! $student && array_key_exists('review_status', $data) ? match ($document->status) {
                'Approved' => 'Approved',
                'Needs changes' => 'Sent back for changes',
                default => 'Review status: '.$document->status,
            } : null,
        ])));

        return $document;
    }

    public static function delete(CrmJourneyDocument $document, ?CrmUser $user): void
    {
        if ($user === null) {
            abort_unless($document->by_student, 403, 'Your counsellor added this one, so only they can remove it.');
            abort_if($document->isEssay() && in_array($document->status, ['Submitted', 'Approved'], true), 403, 'This essay is with your counsellor. Ask them if it should be removed.');
        }

        JourneyLog::record($document->plan, $user, 'Documents', $document->title, 'Removed');

        DB::transaction(function () use ($document): void {
            // The file and every earlier copy kept in its history go with it.
            $paths = array_values(array_unique(array_filter(array_merge([$document->path], $document->edits()->pluck('path')->all()))));
            $document->delete();
            if ($paths !== []) {
                Storage::disk(config('journey.documents.disk'))->delete($paths);
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

    /** A file kept on one entry of a document's history: an earlier copy, or one attached to a note. */
    public static function downloadEdit(CrmJourneyDocument $document, int|string $edit): StreamedResponse
    {
        $entry = $document->edits()->whereKey($edit)->firstOrFail();
        abort_unless($entry->hasFile(), 404);
        $disk = Storage::disk(config('journey.documents.disk'));
        abort_unless($disk->exists($entry->path), 404);

        return $disk->download($entry->path, $entry->original_name ?: 'document', [
            'Content-Type' => $entry->mime ?: 'application/octet-stream',
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
     * @param  callable(CrmJourneyDocument, CrmJourneyDocumentEdit): string  $editFileUrl
     * @return array<string, mixed>
     */
    public static function toArray(CrmJourneyDocument $d, string $mode, callable $fileUrl, callable $editFileUrl): array
    {
        $student = $mode === 'student';
        $mine = $student ? $d->by_student : $mode === 'counsellor';
        $byName = $d->by_student ? ($student ? 'You' : 'Student') : ($d->creator?->name ?: 'Counsellor');

        return [
            'id' => $d->id,
            'kind' => $d->kind,
            'title' => $d->title,
            'category' => $d->category,
            'version' => (int) $d->version,
            'edits' => self::editsToArray($d, $mode, $editFileUrl),
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

    /** Read the validated "log an edit" input off the request: a note, and maybe a new version and a file. */
    public static function editInput(Request $request): array
    {
        $c = config('journey.documents');

        return $request->validate([
            'note' => ['required', 'string', 'max:290'],
            'new_version' => ['sometimes', 'boolean'],
            'file' => ['sometimes', 'nullable', 'file', 'max:'.(int) $c['max_upload_kb'], 'mimes:'.implode(',', $c['extensions'])],
        ], [
            'file.max' => 'That file is larger than '.round($c['max_upload_kb'] / 1024).' MB. Upload a smaller copy.',
            'file.mimes' => 'Upload a PDF, Word document, or JPG/PNG image.',
        ], ['note' => 'what changed']);
    }

    /** The most entries one document's history keeps; the oldest drop off. */
    private const MAX_EDITS = 80;

    /**
     * Add an entry to a document's history. Repeating the same note within
     * half an hour is treated as one sitting: the entry already there is
     * touched instead of another being added, and the version doesn't move
     * again, so saving a draft five times in an afternoon reads as one edit.
     * An entry that brings a file is always its own.
     *
     * @param  array{path?: string, original_name?: string, mime?: ?string, size?: ?int}  $file  a stored file (storeFile)
     */
    public static function recordEdit(CrmJourneyDocument $document, string $note, ?CrmUser $user, bool $newVersion = false, array $file = []): CrmJourneyDocumentEdit
    {
        $note = Str::limit(trim($note) ?: 'Edited', 290);
        $recent = $document->edits()->first();
        $sameSitting = $file === [] && $recent && ! $recent->hasFile() && $recent->note === $note && $recent->by_student === ($user === null)
            && (int) $recent->created_by === (int) $user?->id && $recent->updated_at?->gt(now()->subMinutes(30));

        if ($sameSitting) {
            $recent->touch();

            return $recent;
        }
        if ($newVersion) {
            $document->forceFill(['version' => (int) $document->version + 1])->save();
        }

        $edit = $document->edits()->create([
            'version' => (int) $document->version,
            'note' => $note,
            'by_student' => $user === null,
            'created_by' => $user?->id,
        ] + $file);

        // The oldest entries drop off, and with them any file no longer in use.
        $keep = $document->edits()->limit(self::MAX_EDITS)->pluck('id');
        $gone = $document->edits()->whereNotIn('id', $keep)->get();
        if ($gone->isNotEmpty()) {
            $inUse = array_merge([$document->path], $document->edits()->whereIn('id', $keep)->pluck('path')->all());
            $orphans = array_values(array_diff(array_filter($gone->pluck('path')->all()), array_filter($inUse)));
            $document->edits()->whereNotIn('id', $keep)->delete();
            if ($orphans !== []) {
                Storage::disk(config('journey.documents.disk'))->delete($orphans);
            }
        }

        return $edit;
    }

    /**
     * Someone recording a change they made to the document outside the
     * planner, with a file if they have one. On an uploaded file the new file
     * becomes the current version; the copy it replaces stays on the history
     * entry it came with. On an essay the file sits on the entry, and the
     * version moves only if they say this is a new version.
     */
    public static function noteEdit(CrmJourneyDocument $document, string $note, ?CrmUser $user, bool $newVersion, ?UploadedFile $upload = null): CrmJourneyDocumentEdit
    {
        if ($user === null && ! $document->by_student) {
            abort(403, 'Your counsellor added this one, so only they can record changes to it.');
        }
        $plan = $document->plan;
        $stored = $upload ? self::storeFile($plan, $upload) : [];

        if ($stored !== [] && ! $document->isEssay()) {
            self::keepCurrentFile($document);
            $document->forceFill($stored)->save();
            $newVersion = true;
        }

        JourneyLog::record($plan, $user, 'Documents', $document->title, ($newVersion ? 'New version: ' : 'Edit noted: ').$note
            .($stored !== [] ? ' · file “'.$stored['original_name'].'”' : ''));
        $edit = self::recordEdit($document, $note, $user, $newVersion, $stored);

        if ($stored !== [] && ! $document->isEssay()) {
            self::advanceUploadTask($plan, $document, $user);
            if ($user === null) {
                self::log($plan, 'The student uploaded a new version of “'.$document->title.'” ('.$stored['original_name'].').');
            }
        }

        return $edit;
    }

    /**
     * One document's history as the page needs it.
     *
     * @param  'counsellor'|'partner'|'student'  $mode
     * @param  callable(CrmJourneyDocument, CrmJourneyDocumentEdit): string  $editFileUrl
     * @return list<array<string, mixed>>
     */
    public static function editsToArray(CrmJourneyDocument $d, string $mode, callable $editFileUrl): array
    {
        return $d->edits->map(fn (CrmJourneyDocumentEdit $e) => [
            'id' => $e->id,
            'note' => $e->note,
            'version' => $e->version,
            'byName' => $e->by_student ? ($mode === 'student' ? 'You' : 'Student') : ($e->author?->name ?: 'Counsellor'),
            'at' => $e->created_at?->toIso8601String(),
            'file' => $e->hasFile() ? [
                'name' => $e->original_name,
                'size' => $e->size,
                'url' => $editFileUrl($d, $e),
                // The copy the document is on now.
                'current' => ! $d->isEssay() && $e->path === $d->path,
            ] : null,
        ])->values()->all();
    }

    /**
     * Put an uploaded file on the private disk, under its plan, with a name
     * of our own; what it was called is kept to download it under.
     *
     * @return array{path: string, original_name: string, mime: ?string, size: ?int}
     */
    private static function storeFile(CrmJourneyPlan $plan, UploadedFile $file): array
    {
        $name = Str::limit(trim($file->getClientOriginalName()), 180, '');

        return [
            'path' => $file->storeAs(
                config('journey.documents.directory').'/'.$plan->id,
                Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension()),
                config('journey.documents.disk'),
            ),
            'original_name' => $name,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * Before a newer copy replaces a document's file, make sure the current
     * copy sits on a history entry, so it stays downloadable. Documents
     * uploaded before entries could hold files kept their first copy only on
     * the document: it goes on the "Uploaded" entry it came with, or, if the
     * history has since lost that entry, on a note of its own.
     */
    private static function keepCurrentFile(CrmJourneyDocument $document): void
    {
        if (! $document->path || $document->edits()->where('path', $document->path)->exists()) {
            return;
        }
        $copy = ['path' => $document->path, 'original_name' => $document->original_name, 'mime' => $document->mime, 'size' => $document->size];
        $first = $document->edits()->whereNull('path')->reorder('id')->first();
        if ($first && $first->note === 'Uploaded “'.$document->original_name.'”') {
            $first->forceFill($copy)->save();

            return;
        }
        $document->edits()->create($copy + [
            'version' => (int) $document->version,
            'note' => 'Copy before the new file',
            'by_student' => $document->by_student,
            'created_by' => $document->created_by,
        ]);
    }

    private static function where(CrmJourneyDocument $d): string
    {
        $university = $d->application_id ? CrmJourneyApplication::query()->whereKey($d->application_id)->value('university') : null;

        return $university ? ' for '.$university : '';
    }

    /**
     * A task that asks for a document (the Student Declaration Form) moves to
     * Submitted when that document is uploaded, so the counsellor sees it is
     * waiting for their sign-off. Completing it stays their call.
     */
    private static function advanceUploadTask(CrmJourneyPlan $plan, CrmJourneyDocument $document, ?CrmUser $user): void
    {
        $moved = DB::transaction(function () use ($plan, $document, $user): array {
            $locked = CrmJourneyPlan::query()->lockForUpdate()->find($plan->id);
            $state = $locked->coreState();
            $moved = [];
            foreach ($locked->coreDefinitions() as $key => $def) {
                if (($def['upload'] ?? null) === $document->category && $state[$key]['inc']
                    && in_array($state[$key]['status'], ['Not Started', 'In Progress'], true)) {
                    $state[$key] = JourneyPlanner::apply($state[$key], ['status' => 'Submitted'], $user ? 'team:'.$user->id : 'student');
                    $moved[] = $def['name'];
                }
            }
            if ($moved !== []) {
                $locked->forceFill(['core' => $state])->save();
            }

            return $moved;
        });
        foreach ($moved as $name) {
            JourneyLog::record($plan, $user, 'Core journey', $name, 'Status: Submitted (signed form uploaded)');
        }
    }

    private static function log(CrmJourneyPlan $plan, string $body): void
    {
        CrmLeadActivity::query()->create([
            'crm_lead_id' => $plan->crm_lead_id, 'crm_user_id' => null, 'type' => 'journey_student', 'body' => $body,
        ]);
    }
}
