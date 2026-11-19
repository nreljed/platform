<?php

namespace Nreljed\Platform\Tests;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Nreljed\Platform\Http\Middleware\ContentSecurityPolicy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the security-header contract of the ContentSecurityPolicy middleware.
 *
 * The app had a CSP but none of the baseline hardening headers (HSTS,
 * nosniff, clickjacking protection, referrer/permissions policy), and the
 * policy itself used `frame-src 'none'`, which breaks the Stripe
 * PaymentElement iframe and every video embed. These assertions pin the
 * headers and the third-party origins the policy must keep allowing.
 */
final class ContentSecurityPolicyTest extends TestCase
{
    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function runMiddleware(string $url, string $env = 'production', array $config = []): Response
    {
        $app = new Application(dirname(__DIR__));

        $app->instance('config', new Repository(array_replace_recursive([
            'app' => ['url' => 'https://shop.example.test'],
            'core' => ['csp_enabled' => false],
        ], $config)));
        $app->instance('env', $env);
        $app->instance(Vite::class, new Vite);

        Facade::setFacadeApplication($app);

        $middleware = new ContentSecurityPolicy;

        return $middleware->handle(
            Request::create($url),
            static fn (Request $request): Response => new Response('ok'),
        );
    }

    public function test_production_response_carries_the_baseline_security_headers(): void
    {
        $response = $this->runMiddleware('https://shop.example.test/store');

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame(
            'camera=(), microphone=(), geolocation=()',
            $response->headers->get('Permissions-Policy'),
        );
        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $response->headers->get('Strict-Transport-Security'),
        );
    }

    public function test_hsts_is_not_sent_over_plain_http(): void
    {
        $response = $this->runMiddleware(
            'http://shop.example.test/store',
            'production',
            ['app' => ['url' => 'http://shop.example.test']],
        );

        $this->assertNull($response->headers->get('Strict-Transport-Security'));
        // The rest of the hardening headers still apply.
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_csp_is_applied_in_production_and_forbids_framing(): void
    {
        $csp = $this->runMiddleware('https://shop.example.test/store')
            ->headers->get('Content-Security-Policy');

        $this->assertIsString($csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringNotContainsString("frame-src 'none'", $csp);
    }

    public function test_csp_allows_stripe_and_video_embeds(): void
    {
        $csp = $this->runMiddleware('https://shop.example.test/store')
            ->headers->get('Content-Security-Policy');

        $this->assertIsString($csp);

        // Stripe: script (Stripe.js), connect (PaymentElement confirm) and
        // frames (PaymentElement iframe + 3D Secure challenges).
        $this->assertStringContainsString('script-src', $csp);
        $this->assertStringContainsString('https://js.stripe.com', $csp);
        $this->assertStringContainsString('https://api.stripe.com', $csp);
        $this->assertStringContainsString('https://hooks.stripe.com', $csp);

        // Video embeds: blog YouTubeNode, UrlVideo (YouTube + Vimeo).
        $this->assertStringContainsString('https://www.youtube.com', $csp);
        $this->assertStringContainsString('https://www.youtube-nocookie.com', $csp);
        $this->assertStringContainsString('https://player.vimeo.com', $csp);
        $this->assertStringContainsString('https://vimeo.com', $csp);
    }

    public function test_local_environment_keeps_security_headers_but_skips_the_csp(): void
    {
        $response = $this->runMiddleware('http://shop.example.test/store', 'local');

        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function test_local_environment_can_opt_into_the_csp(): void
    {
        $csp = $this->runMiddleware(
            'http://shop.example.test/store',
            'local',
            ['core' => ['csp_enabled' => true]],
        )->headers->get('Content-Security-Policy');

        $this->assertIsString($csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }
}
