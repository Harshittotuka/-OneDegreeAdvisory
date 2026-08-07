<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CareerCounsellingStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * CMS tab for the Career Counselling page's "Plans & Pricing" section — the
 * school-stage tabs, the plan cards, their feature lists and (the reason this
 * tab exists) every price. A tier's price here is exactly what a parent is
 * charged: the public page only sends back the option index it rendered.
 */
class CareerCounsellingCmsController extends Controller
{
    public function __construct(private CareerCounsellingStore $store)
    {
    }

    public function edit(): View
    {
        return view('admin.career-counselling.edit', [
            'plans' => $this->store->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'heading.eyebrow' => 'nullable|string|max:60',
            'heading.title' => 'nullable|string|max:140',
            'heading.subtitle' => 'nullable|string|max:240',
            'stages' => 'array|max:'.CareerCounsellingStore::MAX_STAGES,
            'stages.*.label' => 'nullable|string|max:60',
            'plans' => 'array',
            'plans.*.name' => 'nullable|string|max:60',
            'plans.*.subtitle' => 'nullable|string|max:140',
            'plans.*.badge' => 'nullable|string|max:40',
            'plans.*.stage' => 'nullable|integer|min:0|max:'.(CareerCounsellingStore::MAX_STAGES - 1),
            'plans.*.feature_title' => 'nullable|array',
            'plans.*.feature_title.*' => 'nullable|string|max:80',
            'plans.*.feature_text' => 'nullable|array',
            'plans.*.feature_text.*' => 'nullable|string|max:240',
            'plans.*.feature_locked' => 'nullable|array',
            'plans.*.feature_locked.*' => 'nullable|string|max:10',
            'plans.*.tier_label' => 'nullable|array',
            'plans.*.tier_label.*' => 'nullable|string|max:40',
            'plans.*.tier_price' => 'nullable|array',
            'plans.*.tier_price.*' => 'nullable|string|max:12',
            'payment.title' => 'nullable|string|max:140',
            'payment.description' => 'nullable|string|max:400',
            'payment.button_label' => 'nullable|string|max:40',
            'payment.enquiry_label' => 'nullable|string|max:40',
            'payment.note' => 'nullable|string|max:300',
            'payment.accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        // Rows arrive keyed by row token; PHP preserves the submitted (DOM)
        // order, so iterating rebuilds the list in the editor's order. Rows with
        // no name are dropped by the store (that is how "remove" persists).
        $plans = [];
        foreach ((array) $request->input('plans', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            // Features and tiers arrive as parallel label[]/value[] inputs rather
            // than nested pairs, so they survive add/remove reordering in the
            // browser without needing matching array keys. "Locked" is a <select>
            // rather than a checkbox for the same reason: an unchecked box submits
            // nothing, which would silently shift every later row's flag.
            $titles = (array) ($row['feature_title'] ?? []);
            $texts = (array) ($row['feature_text'] ?? []);
            $locks = (array) ($row['feature_locked'] ?? []);
            $features = [];
            foreach ($titles as $i => $title) {
                $features[] = [
                    'title' => (string) $title,
                    'text' => (string) ($texts[$i] ?? ''),
                    'locked' => (string) ($locks[$i] ?? '') === 'locked',
                ];
            }

            $tierLabels = (array) ($row['tier_label'] ?? []);
            $tierPrices = (array) ($row['tier_price'] ?? []);
            $tiers = [];
            foreach ($tierPrices as $i => $price) {
                $tiers[] = [
                    'label' => (string) ($tierLabels[$i] ?? ''),
                    'price' => (string) $price,
                ];
            }

            $plans[] = [
                'stage' => (int) ($row['stage'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'subtitle' => (string) ($row['subtitle'] ?? ''),
                'badge' => (string) ($row['badge'] ?? ''),
                'featured' => ! empty($row['featured']),
                'visible' => ! empty($row['visible']),
                'features' => $features,
                'tiers' => $tiers,
            ];
        }

        $this->store->save([
            'heading' => (array) $request->input('heading', []),
            'stages' => (array) $request->input('stages', []),
            'plans' => $plans,
            'payment' => (array) $request->input('payment', []),
        ]);

        return redirect()
            ->route('admin.career-counselling.index')
            ->with('status', 'Career counselling plans & pricing updated.');
    }
}
