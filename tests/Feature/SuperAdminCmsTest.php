<?php

namespace Tests\Feature;

use App\Support\CountryVisibilityStore;
use App\Support\MbbsCountryContent;
use App\Support\StudyLocationContent;
use Tests\TestCase;

class SuperAdminCmsTest extends TestCase
{
    /** Raw backup of the file-backed About page, restored after each test. */
    private ?string $aboutBackup = null;
    private ?string $visibilityBackup = null;
    private bool $visibilityExisted = false;

    private function aboutPath(): string
    {
        return storage_path('app/about-page.json');
    }

    private function visibilityPath(): string
    {
        return storage_path('app/country-visibility.json');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // These tests hit the file-backed About store; snapshot it so a save test
        // can never permanently alter the real page content.
        $this->aboutBackup = is_file($this->aboutPath()) ? file_get_contents($this->aboutPath()) : null;
        $this->visibilityExisted = is_file($this->visibilityPath());
        $this->visibilityBackup = $this->visibilityExisted ? file_get_contents($this->visibilityPath()) : null;

        if ($this->visibilityExisted) {
            unlink($this->visibilityPath());
        }
    }

    protected function tearDown(): void
    {
        if ($this->aboutBackup !== null) {
            file_put_contents($this->aboutPath(), $this->aboutBackup);
        }

        if ($this->visibilityExisted) {
            file_put_contents($this->visibilityPath(), (string) $this->visibilityBackup);
        } elseif (is_file($this->visibilityPath())) {
            unlink($this->visibilityPath());
        }

        parent::tearDown();
    }

    public function test_super_admin_password_logs_in_and_unlocks_about(): void
    {
        $this->post('/admin/login', ['password' => config('site.super_admin_password')])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(session('cms_authenticated'));
        $this->assertTrue(session('cms_super_admin'));

        // The About live editor is reachable (not the in-development placeholder).
        $this->get(route('admin.about.live'))
            ->assertOk()
            ->assertSee('data-le-sec', false);

        // Dashboard advertises super-admin + the About link.
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Super Admin', false)
            ->assertSee(route('admin.about.live'), false);
    }

    public function test_standard_password_logs_in_but_about_stays_locked(): void
    {
        $this->post('/admin/login', ['password' => config('site.cms_password')])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(session('cms_authenticated'));
        $this->assertNotTrue(session('cms_super_admin'));

        // About editor shows the in-development placeholder for a standard admin.
        $this->get(route('admin.about.live'))
            ->assertOk()
            ->assertDontSee('data-le-sec', false);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->post('/admin/login', ['password' => 'not-the-password'])
            ->assertSessionHasErrors('password');

        $this->assertNotTrue(session('cms_authenticated'));
    }

    public function test_mbbs_country_sync_tool_renders_for_admin(): void
    {
        $this->withSession(['cms_authenticated' => true]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('MBBS country data')
            ->assertSee(route('admin.mbbs-country-sync.index'), false);

        $this->get(route('admin.mbbs-country-sync.index'))
            ->assertOk()
            ->assertSee('Sync MBBS countries')
            ->assertSee('AV Global Overseas')
            ->assertSee('Check source changes');
    }

    public function test_updated_nav_is_permanent_and_switcher_is_removed(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('ui-switchers.js', false)
            ->assertDontSee('data-nav-content-switch', false)
            ->assertDontSee('data-nav-content-option', false)
            ->assertDontSee('nav-variant--current', false)
            ->assertDontSee('nav-variant--updated', false)
            ->assertDontSee('data-stripe-trigger="services"', false)
            ->assertDontSee('id="stripe-sec-services"', false)
            ->assertSee('data-stripe-trigger="courses"', false)
            ->assertSee('/mbbs/student', false);
    }

    public function test_cms_lists_unlinked_pages(): void
    {
        $this->withSession(['cms_authenticated' => true]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.unlinked-pages.index'), false);

        $this->get(route('admin.unlinked-pages.index'))
            ->assertOk()
            ->assertSee('Unlinked Pages')
            ->assertSee('not linked from the updated primary navigation')
            ->assertSee('/courses/postgraduate', false)
            ->assertSee('/services/test-preparation', false);
    }

    public function test_country_visibility_tool_hides_frontend_country_pages(): void
    {
        $this->withSession(['cms_authenticated' => true, 'cms_super_admin' => true]);

        $nonMbbsCountries = app(StudyLocationContent::class)->allDestinations();
        $mbbsCountries = app(MbbsCountryContent::class)->allCountries();
        $this->assertNotEmpty($nonMbbsCountries);
        $this->assertNotEmpty($mbbsCountries);

        $hiddenNonMbbs = $this->preferredSlug($nonMbbsCountries, 'study-in-australia');
        $hiddenMbbs = $this->preferredSlug($mbbsCountries, 'georgia');

        $this->get(route('admin.country-visibility.index'))
            ->assertOk()
            ->assertSee('Country Visibility')
            ->assertSee('/countries/'.$hiddenNonMbbs, false)
            ->assertSee('/mbbs/country/'.$hiddenMbbs, false);

        $this->post(route('admin.country-visibility.update'), [
            'visible' => [
                CountryVisibilityStore::GROUP_NON_MBBS => $this->allExcept($nonMbbsCountries, $hiddenNonMbbs),
                CountryVisibilityStore::GROUP_MBBS => $this->allExcept($mbbsCountries, $hiddenMbbs),
            ],
        ])->assertRedirect(route('admin.country-visibility.index'))
            ->assertSessionHas('status', 'Country visibility updated.');

        $this->get('/countries/'.$hiddenNonMbbs)->assertNotFound();
        $this->get('/mbbs/country/'.$hiddenMbbs)->assertNotFound();

        $this->get('/')->assertOk()->assertDontSee('/countries/'.$hiddenNonMbbs, false);
        $this->get('/mbbs/student')->assertOk()->assertDontSee('/mbbs/country/'.$hiddenMbbs, false);
    }

    public function test_about_save_endpoint_works_for_super_admin(): void
    {
        // Round-trip the EXISTING sections (not an empty payload) so the save is
        // authorised + persisted without discarding real content; tearDown then
        // restores the byte-exact original regardless.
        $sections = json_decode((string) file_get_contents($this->aboutPath()), true) ?: [];

        $this->withSession(['cms_authenticated' => true, 'cms_super_admin' => true]);

        $this->post(route('admin.about.live.save'), ['sections' => $sections])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_about_save_endpoint_blocked_for_standard_admin(): void
    {
        $this->withSession(['cms_authenticated' => true]);

        // 423 is returned before any write, so this never touches the store.
        $this->post(route('admin.about.live.save'), ['sections' => []])
            ->assertStatus(423);
    }

    private function preferredSlug(array $countries, string $preferred): string
    {
        $slugs = array_column($countries, 'slug');

        return in_array($preferred, $slugs, true) ? $preferred : (string) $slugs[0];
    }

    private function allExcept(array $countries, string $hidden): array
    {
        return array_values(array_filter(
            array_column($countries, 'slug'),
            fn (string $slug): bool => $slug !== $hidden
        ));
    }
}
