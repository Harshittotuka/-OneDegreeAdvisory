<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmUser;
use App\Models\CrmWebsiteSubmission;
use App\Support\SimpleXlsx;
use App\Support\WebsiteSubmissionData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmWebsiteLeadController extends Controller
{
    public function exportCsv(Request $request): StreamedResponse
    {
        $rows = $this->query($request)->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Submitted at', 'Lead ID', 'Source', 'Name', 'Email', 'Phone', 'Type', 'Section', 'Question', 'Answer']);
            foreach ($rows as $submission) {
                $meta = $submission->meta ?: [];
                $base = [$submission->submitted_at?->toDateTimeString(), $submission->lead->lead_number, $submission->source_label, $meta['name'] ?? $submission->lead->name, $meta['email'] ?? $submission->lead->email, $meta['phone'] ?? $submission->lead->phone, $submission->degree];
                $wrote = false;
                foreach ($submission->sections ?: [] as $section) {
                    foreach ($section['answers'] ?? [] as $answer) {
                        fputcsv($out, [...$base, $section['eyebrow'] ?? '', $answer['label'] ?? '', implode(', ', (array) ($answer['value'] ?? []))]);
                        $wrote = true;
                    }
                }
                if (! $wrote) fputcsv($out, [...$base, '', '', '']);
            }
            fclose($out);
        }, 'crm-website-leads-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportExcel(Request $request): Response
    {
        $tab = WebsiteSubmissionData::tabulate($this->query($request)->get());
        $headers = ['Submitted at', 'Source', 'Name', 'Email', 'Phone', 'Type', ...$tab['questions']];
        $rows = array_map(function (array $row) use ($tab): array {
            return [$row['submitted_at'], $row['source_label'], $row['name'], $row['email'], $row['phone'], $row['degree'], ...array_map(fn ($question) => $row['answers'][$question] ?? '', $tab['questions'])];
        }, $tab['rows']);

        return response(SimpleXlsx::build($headers, $rows, 'Website Leads'), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="crm-website-leads-'.now()->format('Y-m-d').'.xlsx"',
        ]);
    }

    public function download(Request $request, CrmWebsiteSubmission $submission): Response
    {
        $this->guard($request, $submission);
        $filename = Str::slug($submission->source_label.' '.$submission->lead->name).'-'.$submission->submitted_at->format('Y-m-d').'.doc';

        return response(view('crm.website-submission-doc', compact('submission'))->render(), 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function destroy(Request $request, CrmWebsiteSubmission $submission): RedirectResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        abort_unless($user->isSuperAdmin(), 403);
        $submission->delete();

        return back()->with('status', 'Website submission deleted. The CRM lead and its timeline were retained.');
    }

    private function query(Request $request): Builder
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        $query = CrmWebsiteSubmission::query()->with('lead.assignee')
            ->whereHas('lead', fn (Builder $lead) => $lead->visibleTo($user))
            ->latest('submitted_at');
        if ($request->filled('source')) $query->where('source', $request->query('source'));
        if ($search = trim((string) $request->query('search'))) {
            $query->whereHas('lead', fn (Builder $lead) => $lead->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('lead_number', 'like', "%{$search}%")));
        }

        return $query;
    }

    private function guard(Request $request, CrmWebsiteSubmission $submission): void
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        abort_unless($user->isSuperAdmin() || $submission->lead->assigned_to === $user->id, 403);
    }
}
