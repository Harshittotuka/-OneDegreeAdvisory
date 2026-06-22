<?php

namespace Tests\Feature;

use App\Support\BriefSchema;
use Tests\TestCase;

class EuropeBriefPageLayoutTest extends TestCase
{
    public function test_europe_page_uses_the_balanced_journey_and_aligned_pricing_layout(): void
    {
        $this->get('/europe')
            ->assertOk()
            ->assertSee('odp-journey--balanced', false)
            ->assertSee('odp-plans', false)
            ->assertSee('odp-file-plan achiever', false);
    }

    public function test_journey_layout_is_editable_in_the_page_builder(): void
    {
        $layoutField = collect(BriefSchema::type('journey')['fields'] ?? [])
            ->firstWhere('key', 'layout');

        $this->assertNotNull($layoutField);
        $this->assertSame('select', $layoutField['type']);
        $this->assertArrayHasKey('balanced', $layoutField['options']);
        $this->assertArrayHasKey('cards', $layoutField['options']);

        $this->withSession(['cms_authenticated' => true])
            ->get(route('admin.pages.studio', 'europe'))
            ->assertOk()
            ->assertSee('Journey layout')
            ->assertSee('data-field="layout"', false)
            ->assertSee('Balanced steps + finish band');
    }

    public function test_page_builder_renders_the_selected_journey_layout(): void
    {
        $response = $this->withSession(['cms_authenticated' => true])
            ->postJson(route('admin.pages.render'), [
                'type' => 'journey',
                'data' => [
                    'title' => 'Your journey',
                    'layout' => 'cards',
                    'steps' => [
                        [
                            'label' => 'Step 1',
                            'heading' => 'Shortlist',
                            'items' => [['name' => 'Profile review', 'desc' => 'Review the student profile.']],
                        ],
                    ],
                    'final_title' => 'Ready to fly',
                    'final_body' => 'The final step remains editable.',
                ],
            ])
            ->assertOk();

        $this->assertStringContainsString('odp-journey--cards', (string) $response->json('node'));
        $this->assertStringContainsString('Ready to fly', (string) $response->json('node'));
    }
}
