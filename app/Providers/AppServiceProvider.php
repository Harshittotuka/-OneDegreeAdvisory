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
