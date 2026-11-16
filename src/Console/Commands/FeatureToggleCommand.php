<?php

namespace Nreljed\Platform\Console\Commands;

use Illuminate\Console\Command;
use Nreljed\Platform\Services\FeatureRegistry;
use Nreljed\Platform\Services\FeatureSettingsService;

class FeatureToggleCommand extends Command
{
    protected $signature = 'feature:toggle {action : enable or disable} {slug : Feature slug (e.g. shop, posts, public-site, ai)}';

    protected $description = 'Enable or disable a feature programmatically';

    public function handle(FeatureRegistry $registry, FeatureSettingsService $settings): int
    {
        $action = $this->argument('action');
        $slug = $this->argument('slug');

        if (! in_array($action, ['enable', 'disable'], true)) {
            $this->error("Invalid action: {$action}. Use 'enable' or 'disable'.");

            return self::FAILURE;
        }

        $bySlug = [];
        foreach ($registry->allProviders() as $provider) {
            $bySlug[$provider->slug()] = $provider;
        }

        if (! isset($bySlug[$slug])) {
            $this->error("Unknown feature: {$slug}");
            $this->line('Available features: '.implode(', ', array_keys($bySlug)));

            return self::FAILURE;
        }

        $provider = $bySlug[$slug];
        $wanted = $action === 'enable';
        $previous = $settings->setEnabled($slug, $wanted, true);

        if ($previous === $wanted) {
            $this->info("Feature '{$slug}' is already ".($wanted ? 'enabled' : 'disabled').'.');

            return self::SUCCESS;
        }

        if ($previous === true && $wanted === false) {
            $provider->onDisable();
        }

        $this->info("Feature '{$slug}' ".($wanted ? 'enabled' : 'disabled').'.');

        return self::SUCCESS;
    }
}
