<?php

namespace Tests\Feature;

use App\Services\PaymentBlockResolver;
use App\Support\BriefSchema;
use Tests\TestCase;

class PaymentBlockCmsTest extends TestCase
{
    public function test_payment_amount_conversion_uses_razorpay_smallest_currency_unit(): void
    {
        $this->assertSame(999_900, PaymentBlockResolver::rupeesToPaise('₹9,999'));
        $this->assertSame(999_950, PaymentBlockResolver::rupeesToPaise('9999.50'));
        $this->assertNull(PaymentBlockResolver::rupeesToPaise('free'));
        $this->assertNull(PaymentBlockResolver::rupeesToPaise('0'));
    }

    public function test_payment_block_has_editable_plans_layout_copy_and_appearance(): void
    {
        $definition = BriefSchema::type('payment');
        $fields = collect($definition['fields'] ?? [])->keyBy('key');

        $this->assertSame('Secure payment', $definition['label']);
        $this->assertSame('content', $definition['cat']);
        $this->assertSame('repeater', $fields->get('options')['type']);
        $this->assertArrayHasKey('split', $fields->get('layout')['options']);
        $this->assertTrue($fields->has('button_label'));
        $this->assertTrue($fields->has('accent'));
        $this->assertTrue($fields->has('accent2'));
    }

    public function test_cms_can_add_and_render_a_payment_block(): void
    {
        $block = $this->withSession(['cms_authenticated' => true])
            ->getJson(route('admin.pages.block', ['type' => 'payment']))
            ->assertOk()
            ->assertJsonPath('type', 'payment');

        $this->assertStringContainsString('data-field="amount"', (string) $block->json('form'));
        $this->assertStringContainsString('data-field="accent"', (string) $block->json('form'));

        $rendered = $this->withSession(['cms_authenticated' => true])
            ->postJson(route('admin.pages.render'), [
                'type' => 'payment',
                'data' => [
                    'eyebrow' => 'Secure enrolment',
                    'title' => 'Pay the application fee',
                    'description' => 'Admissions approval is required.',
                    'layout' => 'centered',
                    'options' => [[
                        'label' => 'Application fee',
                        'amount' => '9999',
                        'description' => 'One application review',
                        'badge' => 'Secure',
                    ]],
                    'button_label' => 'Request approval',
                    'accent' => '#F05A28',
                    'accent2' => '#2B1FA8',
                ],
            ])->assertOk();

        $node = (string) $rendered->json('node');
        $this->assertStringContainsString('odp-payment--centered', $node);
        $this->assertStringContainsString('Application fee', $node);
        $this->assertStringContainsString('9,999', $node);
        $this->assertStringContainsString('Preview mode', $node);
    }

    public function test_ai_builder_prompt_uses_the_safe_editable_payment_marker(): void
    {
        $this->withSession(['cms_authenticated' => true, 'cms_super_admin' => true])
            ->get(route('admin.pages.studio', 'europe'))
            ->assertOk()
            ->assertSee('ODA_PAYMENT', false)
            ->assertSee('extractPaymentSpec', false)
            ->assertSee("{type:'payment'}", false);
    }

    public function test_standard_cms_admin_can_use_ai_embed_blocks(): void
    {
        $html = '<section class="ai-sec"><h2>AI section</h2><script>window.aiSectionReady=true;</script></section>';
        $storePath = storage_path('app/brief-pages.json');
        $original = is_file($storePath) ? file_get_contents($storePath) : null;

        try {
            $block = $this->withSession(['cms_authenticated' => true])
                ->getJson(route('admin.pages.block', ['type' => 'embed']))
                ->assertOk()
                ->assertJsonPath('type', 'embed');

            $this->assertStringContainsString('data-field="html"', (string) $block->json('form'));

            $rendered = $this->withSession(['cms_authenticated' => true])
                ->postJson(route('admin.pages.render'), [
                    'type' => 'embed',
                    'data' => ['html' => $html],
                ])->assertOk();

            $this->assertStringContainsString($html, (string) $rendered->json('node'));

            $layout = [[
                'id' => 'r1', 'cols' => [[
                    'id' => 'c1', 'span' => 12, 'blocks' => [[
                        'id' => 'b1', 'type' => 'embed', 'visible' => true, 'data' => ['html' => $html],
                    ]],
                ]],
            ]];

            $this->withSession(['cms_authenticated' => true])
                ->postJson(route('admin.pages.save', 'europe'), [
                    'title' => 'Europe',
                    'layout' => $layout,
                ])->assertOk();

            $saved = file_get_contents($storePath);
            $this->assertStringContainsString('window.aiSectionReady=true;', (string) $saved);
        } finally {
            if ($original === null) {
                @unlink($storePath);
            } else {
                file_put_contents($storePath, $original);
            }
        }
    }

    public function test_saving_a_page_with_a_payment_section_requires_authorization(): void
    {
        $layout = [[
            'id' => 'r1', 'cols' => [[
                'id' => 'c1', 'span' => 12, 'blocks' => [[
                    'id' => 'b1', 'type' => 'payment', 'visible' => true, 'data' => [],
                ]],
            ]],
        ]];

        $this->withSession(['cms_authenticated' => true])
            ->postJson(route('admin.pages.save', 'europe'), ['title' => 'Europe', 'layout' => $layout])
            ->assertStatus(403)
            ->assertJsonPath('need_payment_otp', true);
    }

    public function test_payment_section_otp_verify_authorizes_the_session(): void
    {
        $otp = '123456';
        $hash = hash_hmac('sha256', $otp, (string) config('app.key').'|payment-section-otp');
        $state = ['hash' => $hash, 'expires' => now()->addMinutes(10)->timestamp, 'attempts' => 0];

        $this->withSession(['cms_authenticated' => true, 'cms_payment_otp' => $state])
            ->postJson(route('admin.pages.payment-otp.verify'), ['otp' => '000000'])
            ->assertUnprocessable()
            ->assertJsonPath('ok', false);

        $this->withSession(['cms_authenticated' => true, 'cms_payment_otp' => $state])
            ->postJson(route('admin.pages.payment-otp.verify'), ['otp' => $otp])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertSessionHas('cms_payment_otp_ok_until');
    }

    public function test_europe_enrol_buttons_target_the_matching_payment_options(): void
    {
        $this->get('/europe')
            ->assertOk()
            ->assertSee('href="#europe-payment-option-0"', false)
            ->assertSee('href="#europe-payment-option-1"', false)
            ->assertSee('href="#europe-payment-option-2"', false)
            ->assertSee('id="europe-payment-option-0"', false)
            ->assertSee('id="europe-payment-option-1"', false)
            ->assertSee('id="europe-payment-option-2"', false);
    }
}
