<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Placeholder addresses (anything containing "example") are undeliverable: the
 * relay accepts them, retries for hours, then bounces. The lead was never
 * reachable and the bounce noise is unstoppable once sent, so the shared
 * `real_email` rule refuses them on every form that collects an address.
 */
class RealEmailRuleTest extends TestCase
{
    use RefreshDatabase;

    private const PLACEHOLDERS = [
        'rohan@example.test',
        'a@example.com',
        'EXAMPLE@gmail.com',
        'sub@mail.example.co.in',
        'Priya@Example.COM',
    ];

    /** @return array<string, mixed> */
    private function contactPayload(string $email): array
    {
        return [
            'name'  => 'Aanya Mehta',
            'email' => $email,
            'phone' => '+91 90000 00000',
            'level' => 'Undergraduate (1-4 yr)',
        ];
    }

    public function test_the_contact_form_refuses_every_placeholder_shape(): void
    {
        foreach (self::PLACEHOLDERS as $email) {
            Mail::fake();

            $this->postJson(route('contact.submit'), $this->contactPayload($email))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

            // Nothing may reach the relay, and no lead may be recorded.
            Mail::assertNothingSent();
            $this->assertDatabaseMissing('crm_leads', ['email' => mb_strtolower($email)]);
        }
    }

    public function test_the_newsletter_refuses_a_placeholder_address(): void
    {
        $this->postJson(route('newsletter.subscribe'), ['email' => 'reader@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_the_referral_form_refuses_placeholders_for_both_people(): void
    {
        Mail::fake();

        $this->postJson(route('referral.submit'), [
            'referrer_name'  => 'Meera Iyer',
            'referrer_email' => 'meera@example.com',
            'referrer_phone' => '9876500000',
            'student_name'   => 'Rohan Gupta',
            'student_email'  => 'rohan@example.test',
            'student_phone'  => '9876543210',
        ])->assertStatus(422)->assertJsonValidationErrors(['referrer_email', 'student_email']);

        Mail::assertNothingSent();
    }

    /**
     * A placeholder address and a malformed one are the same dead end for the
     * visitor, so both point them at the admissions mailbox instead.
     */
    public function test_both_failure_modes_offer_the_admin_mailbox(): void
    {
        $expected = config('site.forms.email_help');

        $this->assertStringContainsString('@', $expected, 'The guidance must name a mailbox.');
        $this->assertStringNotContainsString('example', mb_strtolower($expected));

        // Placeholder address.
        $this->postJson(route('contact.submit'), $this->contactPayload('aanya@example.com'))
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', $expected);

        // Malformed address - the framework's own `email` rule, same guidance.
        $this->postJson(route('contact.submit'), $this->contactPayload('aanya-at-gmail'))
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', $expected);
    }

    public function test_a_real_address_is_still_accepted(): void
    {
        Mail::fake();

        $this->postJson(route('contact.submit'), $this->contactPayload('aanya@mailbox.test'))
            ->assertOk()->assertJson(['ok' => true]);

        $this->postJson(route('newsletter.subscribe'), ['email' => 'reader@mailbox.test'])
            ->assertOk()->assertJson(['ok' => true]);
    }
}
