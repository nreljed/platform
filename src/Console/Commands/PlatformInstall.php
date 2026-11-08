<?php

namespace Nreljed\Platform\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Nreljed\Platform\Models\Role;
use Nreljed\Platform\PlatformServiceProvider;

class PlatformInstall extends Command
{
    protected $signature = 'platform:install
        {--migrate : Run pending migrations before the wiring report}
        {--seed : Seed roles & permissions and bootstrap the admin user}
        {--scaffold : Write a starter resources/js/page-config.ts if missing}';

    protected $description = 'Verify a consuming app is wired to the platform and bootstrap its data';

    public function handle(): int
    {
        if ($this->option('migrate')) {
            $this->components->info('Running pending migrations...');
            $this->call('migrate', ['--force' => true]);
        }

        if ($this->option('seed')) {
            $this->components->info('Seeding roles & permissions...');
            $this->callSilently('db:seed', [
                '--class' => 'Nreljed\\Platform\\Database\\Seeders\\RolesAndPermissionsSeeder',
                '--force' => true,
            ]);
            $this->call('app:create-admin');
        }

        $this->components->info('Platform wiring report');
        $this->newLine();

        $ok = true;

        foreach ($this->wiringChecks() as $label => $passed) {
            $ok = $ok && $passed;
            $this->components->twoColumnDetail($label, $passed ? '<fg=green>OK</>' : '<fg=red>MISSING</>');
        }

        $this->newLine();

        foreach ($this->unregisteredFeatureProviders() as $provider) {
            $ok = false;
            $this->components->error("Feature provider [{$provider}] is not registered in bootstrap/providers.php.");
        }

        if (! $ok) {
            $this->components->error('Setup incomplete. See docs/new-project.md (module activation checklist).');

            return Command::FAILURE;
        }

        $this->components->info('Platform is wired correctly.');

        if (! is_file(resource_path('js/page-config.ts'))) {
            if ($this->option('scaffold')) {
                $this->scaffoldPageConfig();
            } else {
                $this->newLine();
                $this->components->warn('Frontend: no resources/js/page-config.ts. Run with --scaffold to generate a starter, then spread the register{Name}Pages() kits (docs/new-project.md).');
            }
        }

        $this->printFrontendWiring();

        return Command::SUCCESS;
    }

    protected function scaffoldPageConfig(): void
    {
        $dir = resource_path('js');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.'/page-config.ts';
        $template = <<<'TS'
import type { FoundationAppConfig, PageImport } from "@nreljed/platform/app";
import { registerCoreTemplates } from "@nreljed/platform/dashboard";

// Spread additional module kits here, e.g.:
// import { registerBlogPages } from "@nreljed/blog/pages";
// import { registerShopPages } from "@nreljed/shop-module/pages";

export const pageConfig: FoundationAppConfig = {
  basePages: {
    ...registerCoreTemplates(),
    // ...registerBlogPages(),
    // ...registerShopPages(),
  },
  pageOverrides: {},
};
TS;

        file_put_contents($path, $template);
        $this->components->info('Scaffolded resources/js/page-config.ts');
    }

    protected function printFrontendWiring(): void
    {
        $this->newLine();
        $this->components->info('Frontend wiring (add to vite.config.js + tsconfig.json):');
        $this->line("  Vite aliases: '@nreljed/platform' -> './vendor/nreljed/platform/resources/js'");
        $this->line("  Vite aliases: '@nreljed/blog'     -> './vendor/nreljed/blog/src/Resources/js'");
        $this->line("  tsconfig paths: '@nreljed/platform/*' -> './vendor/nreljed/platform/resources/js/*'");
        $this->line("  CSS: @import '../../vendor/nreljed/platform/resources/css/core.css';");
    }

    /**
     * @return array<string, bool>
     */
    protected function wiringChecks(): array
    {
        return [
            'Package installed (composer.lock)' => $this->composerPackageInstalled('nreljed/platform'),
            'PlatformServiceProvider in bootstrap/providers.php' => $this->providerRegistered(PlatformServiceProvider::class),
            'config/features.php published' => is_file(config_path('features.php')),
            'users table migrated' => Schema::hasTable('users'),
            'roles table migrated' => Schema::hasTable('roles'),
            'roles seeded' => $this->rolesSeeded(),
        ];
    }

    protected function rolesSeeded(): bool
    {
        if (! Schema::hasTable('roles')) {
            return false;
        }

        try {
            return Role::query()->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    protected function unregisteredFeatureProviders(): array
    {
        $unregistered = [];

        foreach ((array) config('features.providers', []) as $provider) {
            if (! class_exists($provider)) {
                $unregistered[] = $provider.' (class not found)';

                continue;
            }

            if (! $this->providerRegistered($provider)) {
                $unregistered[] = $provider;
            }
        }

        return $unregistered;
    }

    protected function providerRegistered(string $provider): bool
    {
        $file = (string) file_get_contents(base_path('bootstrap/providers.php'));

        return str_contains($file, $provider);
    }

    protected function composerPackageInstalled(string $package): bool
    {
        foreach ([base_path('composer.json'), base_path('composer.lock')] as $file) {
            if (! is_file($file)) {
                continue;
            }

            if (str_contains((string) file_get_contents($file), '"'.$package.'"')) {
                return true;
            }
        }

        return false;
    }
}
