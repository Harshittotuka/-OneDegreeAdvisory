<?php

namespace App\Providers;

use App\Models\CrmLead;
use App\Models\CrmLeadActivity;
use App\Models\CrmMockInterviewAttempt;
use App\Models\CrmMockInterviewInvite;
use App\Models\CrmSubscriber;
use App\Models\CrmUser;
use App\Models\CrmWebsiteSubmission;
use App\Models\PaymentAttempt;
use App\Support\CmsCrmBackupManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CmsCrmBackupManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->sealOutboundMailInTests();
        $this->registerCmsCrmBackupObservers();
        $this->registerRealEmailRule();
        $this->registerPageMcpRateLimiter();

        if ($this->app->runningInConsole() && config('backup.enabled')) {
            $this->app->terminating(fn () => $this->app->make(CmsCrmBackupManager::class)->flush());
        }

        // In production every generated URL (canonical tags, sitemap, OG/JSON-LD
        // @ids) must share ONE origin, otherwise Google sees www + non-www +
        // http + https as competing duplicates and splits ranking signals.
        // APP_URL is the single source of truth for that canonical origin; we
        // force the scheme and root so url()/route() never echo back a stray
        // host from the incoming request (e.g. www when canonical is non-www).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            $appUrl = (string) config('app.url');
            if ($appUrl !== '') {
                URL::forceRootUrl($appUrl);
            }
        }
    }

    /**
     * Hard stop on outbound mail whenever the test runner is driving the app.
     *
     * MAIL_MAILER=array only swaps the DEFAULT mailer, and no form uses the
     * default: contact, careers, profiler, referral, payment and CRM all call
     * Mail::mailer('contact_form') and friends explicitly, and those are
     * hardcoded smtp transports reading credentials from .env. A test that
     * posted to a form route therefore sent REAL email to the live admissions
     * inbox - which is exactly what happened on 26 Aug 2026.
     *
     * So rather than trust every test to remember Mail::fake(), rewrite every
     * configured mailer to the array transport. Keyed on the test runner and
     * not on APP_ENV, so `--env=production` cannot punch a hole in it either.
     */
    private function sealOutboundMailInTests(): void
    {
        if (! $this->app->runningUnitTests() && ! defined('PHPUNIT_COMPOSER_INSTALL')) {
            return;
        }

        // Every name the app can pass to Mail::mailer() is a key of
        // mail.mailers, so rewriting the lot leaves no reachable transport.
        $sealed = [];
        foreach (array_keys((array) config('mail.mailers', [])) as $name) {
            $sealed[$name] = ['transport' => 'array'];
        }
        $sealed['array'] = ['transport' => 'array'];

        config(['mail.default' => 'array', 'mail.mailers' => $sealed]);

        // A mailer resolved before this ran would still hold its live
        // transport, so drop any that the manager has already built.
        if ($this->app->resolved('mail.manager')) {
            Mail::forgetMailers();
        }
    }

    /**
     * Throttle for the Page Builder MCP endpoint. Keyed by IP rather than by
     * token, because `initialize` and `tools/list` are deliberately open there
     * so a connector can be added — there is not always a token to key on.
     */
    private function registerPageMcpRateLimiter(): void
    {
        RateLimiter::for('page-mcp', fn (Request $request) => Limit::perMinute(
            (int) config('page_api.mcp.rate_limit', 120)
        )->by((string) $request->ip()));
    }

    /**
     * Placeholder addresses (anything containing "example") are undeliverable.
     * The relay accepts them, retries for hours, then bounces - leaving a trail
     * of delivery failures for a lead that was never reachable. Every public
     * form that collects an address applies this rule.
     */
    private function registerRealEmailRule(): void
    {
        // One message covers both failure modes - a malformed address and a
        // placeholder one - so a visitor whose own inbox will not work still
        // has a way to reach us instead of a dead end.
        $message = (string) config('site.forms.email_help')
            ?: 'Please use a valid email address.';

        Validator::extend(
            'real_email',
            fn ($attribute, $value): bool => ! str_contains(mb_strtolower((string) $value), 'example'),
            $message,
        );

        // The matching message for a malformed address lives in
        // lang/en/validation.php, which merges over the framework's defaults.
    }

    private function registerCmsCrmBackupObservers(): void
    {
        $models = [
            PaymentAttempt::class,
            CrmWebsiteSubmission::class,
            CrmUser::class,
            CrmSubscriber::class,
            CrmLead::class,
            CrmLeadActivity::class,
            CrmMockInterviewInvite::class,
            CrmMockInterviewAttempt::class,
        ];

        foreach ($models as $modelClass) {
            $modelClass::saved(function (Model $model): void {
                if (! $model->wasRecentlyCreated && ! $model->wasChanged()) {
                    return;
                }

                // Login bookkeeping is not business data and would otherwise
                // create a full restore point on every CRM sign-in.
                $meaningfulChanges = array_diff(
                    array_keys($model->getChanges()),
                    ['created_at', 'updated_at', 'last_login_at'],
                );

                if ($model->wasRecentlyCreated || $meaningfulChanges !== []) {
                    app(CmsCrmBackupManager::class)->markDirty('crm-database');
                }
            });

            $modelClass::deleted(
                fn () => app(CmsCrmBackupManager::class)->markDirty('crm-database'),
            );
        }
    }
}
