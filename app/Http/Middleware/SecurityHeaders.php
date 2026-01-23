<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Basic security headers
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', "geolocation=(), microphone=(), camera=()");

        // Content Security Policy
        // Allow Vite dev server in development (ports 5173-5180, localhost and IPv6)
        $viteDevServer = '';
        if (app()->environment('local')) {
            $vitePorts = ['5173', '5174', '5175', '5176', '5177'];
            $viteHosts = [];
            foreach ($vitePorts as $port) {
                $viteHosts[] = "http://localhost:{$port}";
                $viteHosts[] = "http://127.0.0.1:{$port}";
                $viteHosts[] = "http://[::1]:{$port}";
                $viteHosts[] = "ws://localhost:{$port}";
                $viteHosts[] = "ws://127.0.0.1:{$port}";
                $viteHosts[] = "ws://[::1]:{$port}";
            }
            $viteDevServer = implode(' ', $viteHosts);
        }
        
        $csp = [
            "default-src 'self'",
            "img-src 'self' data: https://api.qrserver.com",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$viteDevServer}",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data:",
            "connect-src 'self' {$viteDevServer}",
            "frame-src 'self'",
            "frame-ancestors 'none'",
        ];

        // Only set CSP if not already present (allow overrides elsewhere)
        if (!$response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        }

        // HSTS in production and staging over HTTPS
        if (app()->environment(['production', 'staging']) && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
