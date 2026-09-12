<?php

namespace Nreljed\DashboardCore\Observers;

use Illuminate\Support\Facades\Log;
use Nreljed\DashboardCore\Posts\Models\Post;
use Nreljed\DashboardCore\Posts\Services\PostNotificationService;
use Nreljed\DashboardCore\Services\DashboardService;

/**
 * Keeps dashboard aggregate stats fresh when posts change and dispatches the
 * content-lifecycle notifications (post.published / post.scheduled) on status
 * transitions.
 */
class PostObserver
{
    public function created(Post $post): void
    {
        $this->refreshStats();
        $this->dispatchNotifications($post, null);
    }

    public function updated(Post $post): void
    {
        $this->refreshStats();
        $this->dispatchNotifications($post, $post->getOriginal('status'));
    }

    public function deleted(Post $post): void
    {
        $this->refreshStats();
    }

    protected function refreshStats(): void
    {
        app(DashboardService::class)->clearStatsCache();
    }

    protected function dispatchNotifications(Post $post, ?string $oldStatus): void
    {
        try {
            $post->loadMissing('user');
            app(PostNotificationService::class)->dispatch($post, $oldStatus);
        } catch (\Throwable $e) {
            Log::warning('[PostObserver] Failed to dispatch post notification: '.$e->getMessage());
        }
    }
}