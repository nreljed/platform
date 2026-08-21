<?php

namespace Nreljed\DashboardCore\Ai;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Nreljed\DashboardCore\Ai\Controllers\AiProviderController;
use Nreljed\DashboardCore\Ai\Models\AiProvider;
use Nreljed\DashboardCore\Ai\Policies\AiProviderPolicy;
use Nreljed\DashboardCore\Concerns\InteractsWithFeatures;
use Nreljed\DashboardCore\Contracts\FeatureProvider;

class AiServiceProvider extends ServiceProvider implements FeatureProvider
{
    use InteractsWithFeatures;

    public function name(): string
    {
        return 'AI';
    }

    public function slug(): string
    {
        return 'ai';
    }

    public function boot(): void
    {
        $this->loadRoutes();
        $this->registerPolicies();
    }

    protected function loadRoutes(): void
    {
        Route::middleware(['auth', 'maintenance'])
            ->prefix('dashboard/account')
            ->name('dashboard.account.')
            ->group(__DIR__.'/Routes/web.php');
    }

    protected function registerPolicies(): void
    {
        Gate::policy(AiProvider::class, AiProviderPolicy::class);
    }

    public function permissions(): array
    {
        return [
            ['slug' => 'ai.manage', 'name' => 'Manage AI Providers', 'description' => 'Configure AI provider API keys.', 'group' => 'AI'],
        ];
    }

    public function getNavItems(): array
    {
        return [];
    }

    public function getDashboardData(): array
    {
        if (! Schema::hasTable('ai_providers')) {
            return [];
        }

        return [
            'label' => 'AI Providers',
            'icon' => 'Brain',
            'total' => AiProvider::count(),
        ];
    }

    public function getSharedData(): array
    {
        return [];
    }

    public function getNotificationTypes(): array
    {
        return [];
    }

    public function getEmailTemplates(): array
    {
        return [];
    }

    public function onUserDelete(int $userId): void
    {
        AiProvider::where('user_id', $userId)->delete();
    }
}
