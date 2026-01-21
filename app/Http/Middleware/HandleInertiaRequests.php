<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Services\Settings\SettingsServiceInterface;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    public function __construct(private readonly SettingsServiceInterface $settings)
    {
    }

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
            'appStoreName' => fn () => $this->settings->storeName(),
            'appCurrency' => fn () => $this->settings->currency(),
        ];
    }
}
