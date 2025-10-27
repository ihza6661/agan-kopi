<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLogger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Handle tasks after the response is sent to the browser.
     */
    public function terminate(Request $request, $response): void
    {
        try {
            if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                return;
            }

            $path = $request->path();
            $skips = [
                'up',
                'horizon*',
                'telescope*',
                'debugbar*',
                'midtrans/notification',
            ];
            foreach ($skips as $skip) {
                if (Str::is($skip, $path)) {
                    return;
                }
            }

            if ($request->is('assets/*') || $request->is('storage/*')) {
                return;
            }

            $routeName = $request->route()?->getName();
            $controllerAction = $request->route()?->getActionName();

            $activity = sprintf('%s %s', $request->method(), '/' . ltrim($path, '/'));
            if ($routeName) {
                $activity .= ' [' . $routeName . ']';
            }

            $input = $request->all();
            unset($input['_token'], $input['_method'], $input['password'], $input['password_confirmation']);

            $isSafeMethod = in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true);
            $statusCode = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null;
            $details = [
                'route' => $routeName,
                'action' => $controllerAction,
                'query' => $request->query(),
                'payload' => $isSafeMethod ? (object) [] : $input,
                'status' => $statusCode,
            ];
            $json = json_encode($details, JSON_UNESCAPED_UNICODE);
            $description = Str::limit($json ?: '', 2000, '…');

            ActivityLog::query()->create([
                'user_id' => Auth::id(),
                'activity' => Str::limit($activity, 255, ''),
                'description' => $description,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Silent fail
        }
    }
}
