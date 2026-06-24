<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $scriptSources = ["'self'", "'unsafe-inline'"];
        $styleSources = ["'self'", "'unsafe-inline'"];
        $connectSources = ["'self'"];

        if (app()->environment('local')) {
            $devHosts = collect([
                'localhost',
                '127.0.0.1',
                $request->getHost(),
                env('VITE_DEV_SERVER_HOST'),
            ])
                ->filter(fn ($host) => is_string($host) && $host !== '' && ! str_contains($host, ':') && ! str_contains($host, '[') && ! str_contains($host, ']'))
                ->unique()
                ->values();

            $viteHttpSources = $devHosts
                ->map(fn ($host) => 'http://'.$host.':5173')
                ->all();
            $viteWsSources = $devHosts
                ->map(fn ($host) => 'ws://'.$host.':5173')
                ->all();

            $scriptSources = array_merge($scriptSources, $viteHttpSources);
            $styleSources = array_merge($styleSources, $viteHttpSources);
            $connectSources = array_merge($connectSources, $viteHttpSources, $viteWsSources);
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src ".implode(' ', array_unique($scriptSources)).'; style-src '.implode(' ', array_unique($styleSources))."; img-src 'self' data: https://images.unsplash.com; font-src 'self' data:; connect-src ".implode(' ', array_unique($connectSources))."; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
        );

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
