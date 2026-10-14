<?php

namespace Tests\Feature;

use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    public function test_an_unknown_url_gets_the_site_404_page(): void
    {
        $this->get('/no-such-page-anywhere')
            ->assertNotFound()
            ->assertSee('<title>Page Not Found | One Degree Advisory</title>', false)
            ->assertSee('This page took a <em>gap year</em>.', false)
            // The real site chrome, so every way back in is still there.
            ->assertSee('aria-label="Primary navigation"', false)
            ->assertSee('class="site-footer', false)
            ->assertSee('href="'.route('contact').'"', false)
            ->assertSee('<code title="/no-such-page-anywhere">/no-such-page-anywhere</code>', false);
    }

    public function test_it_is_kept_out_of_the_index_but_its_links_are_followed(): void
    {
        $this->get('https://onedegreeadvisory.com/no-such-page-anywhere')
            ->assertNotFound()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_a_missing_page_under_a_section_shows_no_trail(): void
    {
        // These routes have trails of their own; a 404 under them must not
        // borrow one ("Home › Blog › …" over a page that does not exist).
        foreach (['/blog/no-such-post', '/countries/study-in-atlantis', '/mbbs/country/atlantis'] as $path) {
            $this->get($path)
                ->assertNotFound()
                ->assertSee('gap year', false)
                ->assertDontSee('class="site-crumbs"', false)
                ->assertDontSee('BreadcrumbList', false);
        }
    }

    public function test_a_near_miss_offers_the_page_it_meant(): void
    {
        $this->get('/contact-us')
            ->assertNotFound()
            ->assertSee('Did you mean', false)
            ->assertSeeInOrder(['class="nf-guess"', 'href="'.route('contact').'"'], false);
    }

    public function test_an_address_that_means_nothing_offers_no_guess(): void
    {
        $this->get('/wp-login.php')
            ->assertNotFound()
            ->assertDontSee('class="nf-guess"', false);
    }

    public function test_whatsapp_tells_the_advisor_which_link_broke(): void
    {
        $this->get('/no-such-page-anywhere')
            ->assertNotFound()
            ->assertSee('https://wa.me/'.config('site.contact.phone_e164').'?text='
                .rawurlencode('Hi One Degree, I was looking for '.url('/no-such-page-anywhere').' on your website.'), false)
            ->assertSee('href="tel:+'.config('site.contact.phone_e164').'"', false)
            ->assertSee('href="mailto:'.config('site.contact.email').'"', false);
    }

    public function test_the_miss_is_reported_to_analytics_and_the_finder_is_wired(): void
    {
        $this->get('/no-such-page-anywhere')
            ->assertNotFound()
            ->assertSee('"page_not_found"', false)
            ->assertSee('data-nf-search hidden', false)
            ->assertSee('var pages = [', false);
    }

    public function test_the_requested_path_is_escaped(): void
    {
        $this->get('/%3Cscript%3Ealert(1)%3C%2Fscript%3E')
            ->assertNotFound()
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
