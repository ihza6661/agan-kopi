<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (App::environment(['production', 'staging'])) {
            if (!$request->isSecure()) {
                app('url')->forceScheme('https');
            }
        }
        return $next($request);
    }
}
