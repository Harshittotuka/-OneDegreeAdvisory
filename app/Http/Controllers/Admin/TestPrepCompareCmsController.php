<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\TestPrepCompareStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TestPrepCompareCmsController extends Controller
{
    public function __construct(private TestPrepCompareStore $store)
    {
    }

    public function edit(): View
    {
        return view('admin.test-prep-compare.edit', [
            'compare' => $this->store->get(),
            'styles' => $this->styleOptions(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'style' => 'required|in:'.implode(',', TestPrepCompareStore::STYLES),
            'heading.eyebrow' => 'nullable|string|max:60',
            'heading.title' => 'nullable|string|max:140',
            'heading.subtitle' => 'nullable|string|max:240',
            'programs' => 'array',
            'programs.*.name' => 'nullable|string|max:80',
            'programs.*.price' => 'nullable|string|max:12',
            'programs.*.months' => 'nullable|string|max:8',
            'programs.*.badge' => 'nullable|string|max:40',
            'programs.*.details.eyebrow' => 'nullable|string|max:60',
            'programs.*.details.title' => 'nullable|string|max:140',
            'programs.*.details.tagline' => 'nullable|string|max:200',
            'programs.*.details.advantage' => 'nullable|string|max:600',
            'programs.*.details.source' => 'nullable|string|max:200',
            'programs.*.details.fact_label' => 'nullable|array',
            'programs.*.details.fact_label.*' => 'nullable|string|max:40',
            'programs.*.details.fact_value' => 'nullable|array',
            'programs.*.details.fact_value.*' => 'nullable|string|max:60',
            'programs.*.details.syllabus' => 'nullable|array',
            'programs.*.details.syllabus.*' => 'nullable|string|max:200',
            'payment.eyebrow' => 'nullable|string|max:60',
            'payment.title' => 'nullable|string|max:140',
            'payment.description' => 'nullable|string|max:400',
            'payment.button_label' => 'nullable|string|max:40',
            'payment.note' => 'nullable|string|max:300',
            'payment.accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        // Rows arrive keyed by row token; PHP preserves the submitted (DOM)
        // order, so iterating rebuilds the list in the editor's order. Rows with
        // no name are dropped (that is how "remove" persists).
        $programs = [];
        foreach ((array) $request->input('programs', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $details = is_array($row['details'] ?? null) ? $row['details'] : [];

            // Facts arrive as parallel label[]/value[] inputs (see the admin
            // row partial) rather than a nested facts[i][0]/[1] pair, so they
            // survive add/remove reordering in the browser without needing
            // matching array keys. Zip them back into [label, value] pairs.
            $factLabels = (array) ($details['fact_label'] ?? []);
            $factValues = (array) ($details['fact_value'] ?? []);
            $facts = [];
            foreach ($factLabels as $i => $label) {
                $facts[] = [(string) $label, (string) ($factValues[$i] ?? '')];
            }

            $programs[] = [
                'name' => (string) ($row['name'] ?? ''),
                'price' => (string) ($row['price'] ?? ''),
                'months' => (string) ($row['months'] ?? ''),
                'badge' => (string) ($row['badge'] ?? ''),
                'visible' => ! empty($row['visible']),
                'details' => [
                    'eyebrow' => (string) ($details['eyebrow'] ?? ''),
                    'title' => (string) ($details['title'] ?? ''),
                    'tagline' => (string) ($details['tagline'] ?? ''),
                    'facts' => $facts,
                    'advantage' => (string) ($details['advantage'] ?? ''),
                    'syllabus' => (array) ($details['syllabus'] ?? []),
                    'source' => (string) ($details['source'] ?? ''),
                ],
            ];
        }

        $this->store->save([
            'style' => (string) $request->input('style'),
            'heading' => (array) $request->input('heading', []),
            'programs' => $programs,
            'payment' => (array) $request->input('payment', []),
        ]);

        return redirect()
            ->route('admin.test-prep-compare.index')
            ->with('status', 'Compare & payment section updated.');
    }

    /** Style key => label + one-line description, for the editor's picker. */
    private function styleOptions(): array
    {
        return [
            'bars' => ['label' => 'Bars (default)', 'desc' => 'Horizontal ranked bars — the current look. Toggle price / duration.'],
            'cards' => ['label' => 'Pricing cards', 'desc' => 'A responsive grid of program cards with price, duration and enrol button.'],
            'table' => ['label' => 'Comparison table', 'desc' => 'A compact sortable table — program, price and duration.'],
            'stack' => ['label' => 'Tier list', 'desc' => 'Bold full-width rows, each with a big price and an enrol button.'],
        ];
    }
}
