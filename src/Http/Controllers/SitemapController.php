<?php

namespace Nreljed\Platform\Http\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Nreljed\Platform\Posts\Models\Post;
use Nreljed\Platform\Posts\Models\PostCategory;
use Nreljed\Platform\Posts\Models\PostTag;
use Nreljed\Platform\Services\FeatureRegistry;
use Nreljed\Platform\Services\SettingsService;

class SitemapController extends Controller
{
    public function index()
    {
        $baseUrl = rtrim((string) SettingsService::get('app_url', config('app.url', '')), '/');

        if ($baseUrl === '') {
            $baseUrl = url('/');
        }

        $urls = [
            $this->url($baseUrl.'/', 'daily', '1.0'),
            $this->url($baseUrl.'/about', 'monthly', '0.5'),
            $this->url($baseUrl.'/services', 'monthly', '0.5'),
            $this->url($baseUrl.'/contact', 'monthly', '0.5'),
            $this->url($baseUrl.'/blog', 'daily', '0.8'),
        ];

        $publishedScope = function ($query) {
            $query->where('status', 'published')
                ->where(function ($q) {
                    $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                });
        };

        $posts = Post::query()
            ->where(fn ($q) => $publishedScope($q))
            ->latest('published_at')
            ->get(['slug', 'updated_at']);

        foreach ($posts as $post) {
            $urls[] = $this->url(
                $baseUrl.'/blog/'.$post->slug,
                'weekly',
                '0.7',
                $post->updated_at?->toDateString(),
            );
        }

        $categories = PostCategory::query()
            ->whereHas('posts', fn ($q) => $publishedScope($q))
            ->get(['slug']);

        foreach ($categories as $category) {
            $urls[] = $this->url($baseUrl.'/blog?category='.$category->slug, 'weekly', '0.5');
        }

        $tags = PostTag::query()
            ->whereHas('posts', fn ($q) => $publishedScope($q))
            ->get(['slug']);

        foreach ($tags as $tag) {
            $urls[] = $this->url($baseUrl.'/blog?tag='.$tag->slug, 'weekly', '0.4');
        }

        foreach ($this->getFeatureSitemapUrls($baseUrl) as $entry) {
            $urls[] = $entry;
        }

        $xml = $this->buildXml($urls);

        return app(ResponseFactory::class)->make($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * Merge sitemap entries contributed by enabled feature providers. Entries
     * return a relative `loc`; the site base URL is prefixed here so providers
     * don't need to know their deployment URL.
     *
     * @return list<array<string, string|null>>
     */
    protected function getFeatureSitemapUrls(string $baseUrl): array
    {
        $flat = [];

        foreach (app(FeatureRegistry::class)->getAllSitemapUrls() as $entry) {
            $loc = isset($entry['loc']) && str_starts_with($entry['loc'], '/')
                ? $baseUrl.$entry['loc']
                : $baseUrl.'/'.$entry['loc'];

            $flat[] = $this->url(
                rtrim($loc, '/') ?: $baseUrl.'/',
                $entry['changefreq'] ?? 'weekly',
                $entry['priority'] ?? '0.5',
                $entry['lastmod'] ?? null,
            );
        }

        return $flat;
    }

    /**
     * @return array<string, string|null>
     */
    protected function url(string $loc, string $changefreq, string $priority, ?string $lastmod = null): array
    {
        return [
            'loc' => $loc,
            'changefreq' => $changefreq,
            'priority' => $priority,
            'lastmod' => $lastmod,
        ];
    }

    /**
     * @param  list<array<string, string|null>>  $urls
     */
    protected function buildXml(array $urls): string
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.e($url['loc']).'</loc>';
            if ($url['lastmod']) {
                $lines[] = '    <lastmod>'.$url['lastmod'].'</lastmod>';
            }
            $lines[] = '    <changefreq>'.$url['changefreq'].'</changefreq>';
            $lines[] = '    <priority>'.$url['priority'].'</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }
}
