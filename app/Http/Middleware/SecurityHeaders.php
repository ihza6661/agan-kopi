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

        // Content Security Policy tuned for app needs (Midtrans Snap + QR API)
        $isProd = (bool) config('midtrans.is_production', false);
        $midtrans = $isProd ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
        
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
            // Allow Midtrans Snap script; keep inline for Blade stacks and Bootstrap inline styles
            // In development, also allow Vite dev server
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$midtrans} {$viteDevServer}",
            // Allow Google Fonts and Bunny Fonts stylesheets
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
            // Allow font files from Google Fonts and Bunny Fonts
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data:",
            // Allow XHR/fetch to Midtrans and Vite dev server
            "connect-src 'self' {$midtrans} {$viteDevServer}",
            // Allow embedding Midtrans Snap iframe
            "frame-src 'self' {$midtrans}",
            // Do not allow our pages to be framed by other sites
            "frame-ancestors 'none'",
        ];

        // Only set CSP if not already present (allow overrides elsewhere)
        if (!$response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        }

        // HSTS only in production over HTTPS
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
