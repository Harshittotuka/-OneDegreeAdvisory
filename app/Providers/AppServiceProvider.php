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
use Illuminate\Database\Eloquent\Model;
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
        $this->registerCmsCrmBackupObservers();
        $this->registerRealEmailRule();

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
