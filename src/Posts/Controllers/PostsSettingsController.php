<?php

namespace Nreljed\DashboardCore\Posts\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Nreljed\DashboardCore\Enums\AuditAction;
use Nreljed\DashboardCore\Http\Controllers\Controller;
use Nreljed\DashboardCore\Services\AuditService;
use Nreljed\DashboardCore\Services\SettingsSanitizer;
use Nreljed\DashboardCore\Services\SettingsService;

/**
 * Posts feature settings surfaced on the unified admin settings page at
 * `/dashboard/admin/settings/posts`. Stored in the `settings` table under the
 * `posts` group, mirroring the WordPress plugin's per-module options.
 */
class PostsSettingsController extends Controller
{
    private const string GROUP = 'posts';

    public function index(): Response
    {
        $settings = Schema::hasTable('settings') ? SettingsService::getGroup(self::GROUP) : [];

        return Inertia::render('posts/settings', [
            'settings' => [
                'show_word_count' => ($settings['show_word_count'] ?? '1') === '1' ? '1' : '0',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('manage-settings');

        $sanitized = [
            'show_word_count' => SettingsSanitizer::bool($request->all(), 'show_word_count', true) ? '1' : '0',
        ];

        SettingsService::setMany($sanitized);
        SettingsService::clearCache();

        AuditService::log(AuditAction::SettingsChanged, $request->user()->id, [
            'changed_keys' => array_keys($sanitized),
        ]);

        return back()->with('status', 'Posts settings saved successfully.');
    }
}