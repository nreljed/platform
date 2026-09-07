<?php

namespace Nreljed\DashboardCore\Concerns;

use Nreljed\DashboardCore\Services\PackageVersionService;

/**
 * Safe no-op defaults for the optional capability methods of the
 * FeatureProvider contract.
 *
 * Providers `use` this trait and override only the methods they support.
 * The identity methods `name()` and `slug()` are intentionally NOT provided
 * here so a feature without them fails to load.
 */
trait InteractsWithFeatures
{
    public function description(): ?string
    {
        return null;
    }

    public function version(): string
    {
        $package = $this->getComposerPackage();

        if ($package !== null && $package !== '') {
            try {
                $installed = app(PackageVersionService::class)->installedVersion($package);

                if ($installed !== null && $installed !== '') {
                    return $installed;
                }
            } catch (\Throwable) {
                // fall through to the declared default
            }
        }

        return '1.0.0';
    }

    /**
     * Return the fully-qualified Composer package that this feature ships as,
     * e.g. "nreljed/shop-module". Used by the system-health page to display
     * the *installed* (Composer-resolved) version rather than the declared
     * version(). Returns null for features bundled inside another package
     * (Blog ships inside dashboard-core).
     */
    public function getComposerPackage(): ?string
    {
        return null;
    }

    public function dependencies(): array
    {
        return [];
    }

    public function enabled(): bool
    {
        try {
            $settings = $this->featureSettingsService();

            return $settings->isEnabled($this->slug(), $this->defaultEnabled());
        } catch (\Throwable) {
            // Registry/service unavailable (e.g. pre-boot, no settings table,
            // seeders/console) — degrade to the config default rather than
            // failing the whole request.
            return $this->defaultEnabled();
        }
    }

    /**
     * The default enabled state for this feature when no persisted override
     * exists. A feature listed in `core.features` is on unless the
     * `core.features_enabled.<slug>` config explicitly disables it.
     */
    protected function defaultEnabled(): bool
    {
        $configured = config('core.features_enabled.'.$this->slug(), null);

        return $configured ?? true;
    }

    protected function featureSettingsService(): \Nreljed\DashboardCore\Services\FeatureSettingsService
    {
        return app(\Nreljed\DashboardCore\Services\FeatureSettingsService::class);
    }

    public function onDisable(): void
    {
        // No-op default. Providers override to clean up state that must not
        // survive while the feature is disabled. Data is preserved so the
        // feature can be re-enabled non-destructively.
    }

    public function getNavGroupLabel(): ?string
    {
        return null;
    }

    public function getNavItems(): array
    {
        return [];
    }

    public function getDashboardData(): array
    {
        return [];
    }

    public function getAnalyticsSeries(): array
    {
        return [];
    }

    public function getSharedData(): array
    {
        return [];
    }

    public function getSettingsRoute(): ?string
    {
        return null;
    }

    public function hasSettings(): bool
    {
        return false;
    }

    public function getMetaFormattingConfig(): array
    {
        return [];
    }

    public function getRepeaterConfig(): array
    {
        return [];
    }

    public function getGalleryConfig(): array
    {
        return [];
    }

    public function getProfileFieldsConfig(): array
    {
        return [];
    }

    public function getProfileSections(): array
    {
        return [];
    }

    public function getEmailTemplates(): array
    {
        return [];
    }

    public function getNotificationTypes(): array
    {
        return [];
    }

    public function getOrphanMediaTargets(): array
    {
        return [];
    }

    public function getSettingsGroups(): array
    {
        return [];
    }

    public function permissions(): array
    {
        return [];
    }

    public function onUserDelete(int $userId): void {}
}
