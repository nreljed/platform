<?php

namespace Nreljed\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));
        Vite::useCspNonce($nonce);
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        // Baseline hardening headers. Applied in every environment (they are
        // inert in local dev) and independent of the CSP gate below.
        $this->applySecurityHeaders($response, $request);

        // Skip only in local development unless explicitly enabled (e.g. staging
        // and other non-prod environments still get the policy). In production it
        // always applies.
        if (app()->environment('local') && ! config('core.csp_enabled', false)) {
            return $response;
        }

        $csp = $this->buildPolicy($nonce);
        $response->headers->set('Content-Security-Policy', $csp);

        $endpoint = config('app.url').'/csp-report';
        $response->headers->set('Reporting-Endpoints', sprintf('csp-endpoint="%s"', $endpoint));

        return $response;
    }

    /**
     * Baseline security headers, independent of the Content-Security-Policy.
     *
     * - nosniff: browsers must not MIME-sniff responses.
     * - X-Frame-Options / frame-ancestors: nothing may embed the app.
     * - Referrer-Policy: cross-origin requests only carry the origin.
     * - Permissions-Policy: the app never uses camera/microphone/geolocation
     *   (no getUserMedia or geolocation calls anywhere), so deny them outright.
     *   `payment` is deliberately NOT denied: Apple Pay / Google Pay go through
     *   the Payment Request API inside the Stripe Payment Element.
     */
    protected function applySecurityHeaders(Response $response, Request $request): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS is only honoured over HTTPS. The app.url fallback covers
        // TLS-terminating proxies where the framework cannot see the scheme;
        // browsers ignore the header arriving over plain HTTP, so that
        // fallback cannot poison a plaintext dev server.
        if ($request->isSecure() || str_starts_with((string) config('app.url'), 'https://')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }

    protected function buildPolicy(string $nonce): string
    {
        $appUrl = config('app.url');
        $wsUrl = str_replace(['https://', 'http://'], ['wss://', 'ws://'], $appUrl);

        $directives = [
            "default-src 'self'",
            // Stripe.js must load straight from Stripe's domain (PCI requirement).
            "script-src 'self' 'nonce-{$nonce}' https://js.stripe.com https://*.js.stripe.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data:",
            "img-src 'self' data: blob: https:",
            // api.stripe.com: Payment Element confirm calls; link.com: Link
            // auth UI when the wallet is offered; vimeo.com: oEmbed thumbnail
            // lookup in the UrlVideo component.
            "connect-src 'self' {$wsUrl} https://api.stripe.com https://vimeo.com https://link.com https://*.link.com",
            "worker-src 'self' blob:",
            // 'none' would kill the Stripe PaymentElement iframe, 3D Secure
            // challenges (hooks.stripe.com) and every video embed (YouTube,
            // Vimeo) rendered by the blog and the UrlVideo component.
            'frame-src https://js.stripe.com https://*.js.stripe.com https://hooks.stripe.com https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com https://link.com https://*.link.com',
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            'report-to csp-endpoint',
        ];

        return implode('; ', $directives);
    }
}
