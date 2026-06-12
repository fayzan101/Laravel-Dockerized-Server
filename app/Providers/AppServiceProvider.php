<?php

namespace App\Providers;

use App\Models\Feature;
use App\Models\Integration;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\AdminPolicy;
use App\Policies\CompliancePolicy;
use App\Policies\DataPolicy;
use App\Policies\FeaturePolicy;
use App\Policies\IamPolicy;
use App\Policies\IntegrationPolicy;
use App\Policies\TenantPolicy;
use App\Policies\UsagePolicy;
use App\Policies\UserPolicy;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Tenant::class => TenantPolicy::class,
        User::class => UserPolicy::class,
        Integration::class => IntegrationPolicy::class,
        Role::class => IamPolicy::class,
        Permission::class => IamPolicy::class,
        Feature::class => FeaturePolicy::class,
    ];

    public function register(): void
    {
    }

    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            $maxAttempts = app()->environment('testing') ? 5 : 10;

            return Limit::perMinute($maxAttempts)->by($request->ip());
        });

        RateLimiter::for('api-lists', function (Request $request) {
            $maxAttempts = app()->environment('testing') ? 10 : 120;

            return Limit::perMinute($maxAttempts)->by($request->user()?->id ?: $request->ip());
        });

        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        Gate::define('admin.list-tenants', fn (User $user) => app(AdminPolicy::class)->listTenants($user));
        Gate::define('admin.create-tenant', fn (User $user) => app(AdminPolicy::class)->createTenant($user));
        Gate::define('admin.view-tenant', fn (User $user, Tenant $tenant) => app(AdminPolicy::class)->viewTenant($user, $tenant));
        Gate::define('admin.update-tenant', fn (User $user, Tenant $tenant) => app(AdminPolicy::class)->updateTenant($user, $tenant));
        Gate::define('admin.delete-tenant', fn (User $user, Tenant $tenant) => app(AdminPolicy::class)->deleteTenant($user, $tenant));
        Gate::define('admin.view-tenant-usage', fn (User $user, Tenant $tenant) => app(AdminPolicy::class)->viewTenantUsage($user, $tenant));
        Gate::define('admin.suspend-tenant', fn (User $user, Tenant $tenant) => app(AdminPolicy::class)->suspendTenant($user, $tenant));
        Gate::define('admin.reactivate-tenant', fn (User $user, Tenant $tenant) => app(AdminPolicy::class)->reactivateTenant($user, $tenant));
        Gate::define('admin.impersonate', fn (User $user) => app(AdminPolicy::class)->impersonate($user));
        Gate::define('admin.dashboard', fn (User $user) => app(AdminPolicy::class)->viewDashboard($user));
        Gate::define('admin.settings.view', fn (User $user) => app(AdminPolicy::class)->viewSettings($user));
        Gate::define('admin.settings.update', fn (User $user) => app(AdminPolicy::class)->updateSettings($user));
        Gate::define('admin.cross-tenant-migrate', fn (User $user) => app(AdminPolicy::class)->crossTenantMigrate($user));

        Gate::define('data.export', fn (User $user) => app(DataPolicy::class)->export($user));
        Gate::define('data.import', fn (User $user) => app(DataPolicy::class)->import($user));
        Gate::define('data.migrate', fn (User $user) => app(DataPolicy::class)->migrate($user));

        Gate::define('usage.view', fn (User $user) => app(UsagePolicy::class)->view($user));
        Gate::define('usage.report', fn (User $user) => app(UsagePolicy::class)->report($user));

        Gate::define('compliance.view-audit-logs', fn (User $user) => app(CompliancePolicy::class)->viewAuditLogs($user));
        Gate::define('compliance.view-activity-logs', fn (User $user) => app(CompliancePolicy::class)->viewActivityLogs($user));
        Gate::define('compliance.gdpr-export', fn (User $user) => app(CompliancePolicy::class)->gdprExport($user));
        Gate::define('compliance.gdpr-delete', fn (User $user) => app(CompliancePolicy::class)->gdprDelete($user));
        Gate::define('compliance.export-audit-logs', fn (User $user) => app(CompliancePolicy::class)->exportAuditLogs($user));
        Gate::define('compliance.report', fn (User $user) => app(CompliancePolicy::class)->viewComplianceReport($user));
        Gate::define('compliance.archive-logs', fn (User $user) => app(CompliancePolicy::class)->archiveLogs($user));

        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            static $printed = false;

            if ($printed || $event->command !== 'serve') {
                return;
            }

            $printed = true;

            Artisan::call('route:list', [
                '--path' => 'api',
            ]);

            $output = trim(Artisan::output());

            if ($output !== '') {
                $event->output->writeln('');
                $event->output->writeln('API Routes:');
                $event->output->writeln($output);
                $event->output->writeln('');
            }
        });
    }
}
