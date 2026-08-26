<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Second line of defence behind AppServiceProvider::sealOutboundMailInTests().
     *
     * A test run once delivered live referral, payment and profiler mail to the
     * client's inbox, so the suite refuses to run at all if the seal ever goes
     * missing. This fires after the app boots but before the test body, so a
     * broken seal fails loudly instead of quietly mailing real people.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $reachable = [];
        foreach ((array) config('mail.mailers', []) as $name => $mailer) {
            $transport = (string) (((array) $mailer)['transport'] ?? '');
            if (! in_array($transport, ['array', 'log'], true)) {
                $reachable[] = $name.' ('.$transport.')';
            }
        }

        $this->assertSame([], $reachable, 'Outbound mail is not sealed for tests - these mailers can reach a real mail server: '.implode(', ', $reachable));
    }
}
