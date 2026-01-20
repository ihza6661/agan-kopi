<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'notifications' => fn () => $user
                ? $user->unreadNotifications()->latest()->limit(10)->get()
                : [],
            'unreadNotificationsCount' => fn () => $user
                ? $user->unreadNotifications()->count()
                : 0,
            'appStoreName' => fn () => Setting::where('key', 'store_name')->value('value') ?? config('app.name', 'POS'),
            'appCurrency' => fn () => Setting::where('key', 'currency')->value('value') ?? 'IDR',
        ];
    }
}
