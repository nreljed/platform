<?php

namespace Nreljed\DashboardCore\Contracts;

/**
 * Contract for self-contained feature providers discovered by the
 * FeatureRegistry.
 *
 * Implement this interface on a service provider and register the class in
 * the `core.features` config. A provider that does not implement the
 * interface fails loudly at boot instead of breaking at request time.
 *
 * Providers should combine this interface with the
 * InteractsWithFeatures trait, which supplies safe no-op defaults for every
 * capability method, and then override the methods they actually support.
 *
 * The only methods a provider must implement itself are `name()` and
 * `slug()`.
 */
interface FeatureProvider
{
    public function name(): string;

    public function slug(): string;

    public function description(): ?string;

    public function version(): string;

    /**
     * Return the fully-qualified Composer package this feature ships as (e.g.
     * "nreljed/shop-module"), or null when bundled inside core. Optional —
     * InteractsWithFeatures provides a null default. Used to resolve the real
     * installed version for the system-health page.
     */
    public function getComposerPackage(): ?string;

    public function dependencies(): array;

    /**
     * Whether this feature is currently enabled.
     *
     * Two-level resolution (highest precedence first), mirrored from the
     * platform module system:
     *  1. Persisted override (settings table, group `core`, key
     *     `feature_enabled.<slug>`) — written by the module-management admin
     *     page and the toggle endpoint.
     *  2. Config default (`core.features_enabled.<slug>`) — a feature listed
     *     in `core.features` is enabled unless this config turns it off.
     *
     * A feature that is disabled contributes no routes (its provider gates
     * route loading on this), no nav, no dashboard data, no settings and no
     * permissions to the FeatureRegistry aggregators.
     *
     * @return bool
     */
    public function enabled(): bool;

    /**
     * Called when the feature transitions from enabled to disabled through
     * the module-management admin page.
     *
     * Implementations should clean up any state that must not survive while
     * the feature is off (e.g. de-prioritise jobs, disable listeners). The
     * default is a no-op; providers opt into cleanup. Data is intentionally
     * NOT destroyed so the feature can be re-enabled non-destructively.
     *
     * @return void
     */
    public function onDisable(): void;

    public function getNavGroupLabel(): ?string;

    public function getNavItems(): array;

    public function getDashboardData(): array;

    public function getAnalyticsSeries(): array;

    public function getSharedData(): array;

    /**
     * Return the NAME of the feature's settings route (not a resolved URL),
     * or null when the feature has no settings page. The registry resolves
     * the route lazily, so a stale or missing route name degrades to "no
     * settings link" instead of throwing.
     */
    public function getSettingsRoute(): ?string;

    public function hasSettings(): bool;

    public function getMetaFormattingConfig(): array;

    public function getRepeaterConfig(): array;

    public function getGalleryConfig(): array;

    public function getProfileFieldsConfig(): array;

    /**
     * Return a list of profile section keys that this feature wants to opt
     * into on the `/dashboard/account/profile` page.
     *
     * The core profile page renders a slot that resolves each returned key to
     * a core React section component. This lets features extend the account
     * area (e.g. `['address']` for shop billing/shipping) WITHOUT overriding
     * the whole profile page component.
     */
    public function getProfileSections(): array;

    public function getEmailTemplates(): array;

    public function getNotificationTypes(): array;

    /**
     * Return a map of storage folder => model class that this feature wants
     * scanned by the `core:cleanup-orphan-media` command, e.g.
     * `['products' => Product::class]`. Aggregated alongside the host
     * `core.orphan_media` config.
     *
     * @return array<string, class-string>
     */
    public function getOrphanMediaTargets(): array;

    /**
     * Return the names of additional settings groups this feature owns, e.g.
     * `['shop']`. Aggregated into SettingsService::groups() alongside the
     * host `core.settings.groups` config.
     *
     * @return array<int, string>
     */
    public function getSettingsGroups(): array;

    public function permissions(): array;

    public function onUserDelete(int $userId): void;
}
