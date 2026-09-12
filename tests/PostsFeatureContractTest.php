<?php

namespace Nreljed\Platform\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SplFileInfo;

/**
 * Structural contract test for the Posts feature's content-lifecycle
 * notification wiring. Package tests run without a booted application, so it
 * validates the notification types, email template catalog and shipped blade
 * files via reflection and filesystem checks only — the delivery behavior is
 * covered by the host application's integration tests.
 */
final class PostsFeatureContractTest extends TestCase
{
    public function test_posts_notification_types_wire_email_templates(): void
    {
        $provider = $this->providerInstance();
        $types = $provider->getNotificationTypes();

        foreach (['post.published' => 'post-published', 'post.scheduled' => 'post-scheduled'] as $type => $template) {
            $this->assertArrayHasKey($type, $types, "$type must be declared.");
            $this->assertContains('email', $types[$type]['default_channels'] ?? [], "$type must default to email delivery.");
            $this->assertSame($template, $types[$type]['email_template'] ?? null, "$type must wire to the $template template.");
            $this->assertSame(['post_id'], $types[$type]['entity_id_keys'] ?? [], "$type must map its entity id.");
        }
    }

    public function test_email_template_catalog_matches_notification_types(): void
    {
        $provider = $this->providerInstance();
        $templates = $provider->getEmailTemplates();

        foreach ($this->notificationTemplateKeys() as $key) {
            $this->assertArrayHasKey($key, $templates, "Email template $key must be registered in getEmailTemplates().");
            $this->assertArrayHasKey('label', $templates[$key], "Email template $key must declare a label.");
            $this->assertArrayHasKey('sample_data', $templates[$key], "Email template $key must declare sample data for preview.");
        }
    }

    public function test_email_template_blades_ship_with_the_package(): void
    {
        $base = dirname(__DIR__).'/src/Posts/Resources/views/email/templates';

        foreach ($this->notificationTemplateKeys() as $key) {
            $file = new SplFileInfo($base.'/'.$key.'.blade.php');
            $this->assertTrue($file->isFile(), "Blade template $key.blade.php must ship under the posts namespace.");
        }
    }

    public function test_provider_registers_posts_view_namespace(): void
    {
        $file = file_get_contents((new ReflectionClass($this->providerInstance()))->getFileName());
        $this->assertStringContainsString("loadViewsFrom(__DIR__.'/Resources/views', 'posts')", (string) $file, 'Provider must register the posts:: view namespace for its templates.');
    }

    public function test_seo_agent_schema_declares_term_suggestions(): void
    {
        $source = (string) file_get_contents((new ReflectionClass(\Nreljed\Platform\Ai\Agents\SeoAgent::class))->getFileName());

        $this->assertStringContainsString(
            "'suggested_category' => \$schema->string()->nullable()",
            $source,
            'SeoAgent schema must declare an optional suggested_category.'
        );
        $this->assertStringContainsString(
            "'suggested_tags' => \$schema->array()->items(\$schema->string())->max(5)",
            $source,
            'SeoAgent schema must declare up to five suggested_tags.'
        );
    }

    public function test_seo_suggestion_resolver_contract(): void
    {
        $reflection = new ReflectionClass(\Nreljed\Platform\Posts\Services\SeoSuggestionResolver::class);
        $this->assertTrue($reflection->isInstantiable());
        $method = $reflection->getMethod('resolve');

        $this->assertSame('array', (string) $method->getReturnType(), 'resolve() must return the enriched array.');
        $this->assertCount(1, $method->getParameters(), 'resolve() must take exactly the incoming SEO payload.');
    }

    public function test_seo_suggestion_resolver_wired_into_generate_paths(): void
    {
        foreach ([
            \Nreljed\Platform\Posts\Controllers\SeoGenerationController::class,
            \Nreljed\Platform\Jobs\GenerateSeoForPost::class,
        ] as $class) {
            $source = (string) file_get_contents((new ReflectionClass($class))->getFileName());
            $this->assertStringContainsString(
                'SeoSuggestionResolver',
                $source,
                "$class must resolve term suggestions before persisting/responding."
            );
        }
    }

    public function test_demo_seed_commands_registered_and_delegate_to_configured_seeder(): void
    {
        $providerSource = (string) file_get_contents((new ReflectionClass(\Nreljed\Platform\PlatformServiceProvider::class))->getFileName());

        foreach ([
            \Nreljed\Platform\Console\Commands\PostsSeed::class,
            \Nreljed\Platform\Console\Commands\PostsClean::class,
        ] as $class) {
            $this->assertTrue(class_exists($class), "$class must exist.");
            $fqcn = (string) $class;
            $this->assertStringContainsString(
                'Console\\Commands\\'.substr($fqcn, strrpos($fqcn, '\\') + 1),
                $providerSource,
                "PlatformServiceProvider must register $class."
            );

            $signature = (new ReflectionClass($class))->getProperty('signature')->getDefaultValue();
            $this->assertIsString($signature, "$class must declare a signature.");
        }
    }

    public function test_posts_seed_reads_blog_demo_seeder_from_config_catalog(): void
    {
        $source = (string) file_get_contents((new ReflectionClass(\Nreljed\Platform\Console\Commands\PostsSeed::class))->getFileName());

        $this->assertStringContainsString(
            "database_maintenance.seeders.",
            $source,
            'posts:seed must resolve the demo seeder class from the database_maintenance config catalog.'
        );
        $this->assertStringContainsString("--class", $source, 'posts:seed must delegate to db:seed --class.');
    }

    public function test_posts_clean_removes_only_demo_marked_posts(): void
    {
        $source = (string) file_get_contents((new ReflectionClass(\Nreljed\Platform\Console\Commands\PostsClean::class))->getFileName());

        $this->assertStringContainsString(
            "metadata->_demo",
            $source,
            'posts:clean must scope deletion to posts marked metadata._demo = group.'
        );
    }

    public function test_provider_declares_posts_content_description(): void
    {
        $description = $this->providerInstance()->getContentDescription();

        $this->assertNotNull($description, 'Posts feature must declare a ContentDescription.');
        $this->assertSame('post', $description->getSlug());
        $this->assertSame(\Nreljed\Platform\Posts\Models\Post::class, $description->getModelClass());
        $this->assertSame('posts', $description->getCapabilityBase());
        $this->assertTrue($description->hasFeature('seo'));
        $this->assertTrue($description->hasFeature('scheduled'));

        $keys = array_column($description->getTaxonomies(), 'key');
        $this->assertContains('category', $keys);
        $this->assertContains('tag', $keys);
    }

    public function test_provider_declares_scheduled_publish_task(): void
    {
        $tasks = $this->providerInstance()->getScheduledTasks();

        $this->assertNotEmpty($tasks, 'Posts feature must declare its scheduled task.');
        $this->assertSame(\Nreljed\Platform\Console\Commands\PublishScheduledContent::class, $tasks[0]['command'] ?? null);
        $this->assertNotEmpty($tasks[0]['frequency'] ?? '');
    }

    public function test_schedule_publish_is_wired_into_core_schedule(): void
    {
        $source = (string) file_get_contents((new ReflectionClass(\Nreljed\Platform\PlatformServiceProvider::class))->getFileName());

        $this->assertStringContainsString("schedule:publish", $source, 'Core scheduler must register schedule:publish.');
        $this->assertStringContainsString("everyMinute", $source, 'schedule:publish must run every minute.');
        $this->assertStringContainsString("withoutOverlapping", $source, 'schedule:publish must not overlap runs.');
    }

    public function test_cleanup_orphan_media_recurses_month_folders(): void
    {
        $source = (string) file_get_contents((new ReflectionClass(\Nreljed\Platform\Console\Commands\CleanupOrphanMedia::class))->getFileName());

        $this->assertStringContainsString('allFiles', $source, 'CleanupOrphanMedia must recurse YYYY/m subfolders instead of a top-level file scan.');
        $this->assertStringContainsString('pruneEmptyDirectories', $source, 'CleanupOrphanMedia must prune swept-empty subdirectories.');
    }

    /**
     * @return array<int, string>
     */
    private function notificationTemplateKeys(): array
    {
        $provider = $this->providerInstance();
        $keys = [];

        foreach ($provider->getNotificationTypes() as $type) {
            $template = $type['email_template'] ?? null;
            if ($template !== null) {
                $keys[] = $template;
            }
        }

        return $keys;
    }

    /**
     * @return \Nreljed\Platform\Posts\PostsServiceProvider
     */
    private function providerInstance(): object
    {
        $reflection = new ReflectionClass(\Nreljed\Platform\Posts\PostsServiceProvider::class);

        return $reflection->newInstanceWithoutConstructor();
    }
}