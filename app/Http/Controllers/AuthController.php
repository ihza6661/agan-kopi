<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthServiceInterface;
use App\Services\ActivityLog\ActivityLoggerInterface;
use App\Services\Settings\SettingsServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $auth,
        private readonly ActivityLoggerInterface $logger,
        private readonly SettingsServiceInterface $settings
    ) {}

    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login', [
            'appStoreName' => $this->settings->get('store_name', config('app.name', 'POS')),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $ok = $this->auth->login(
            $validated['email'],
            $validated['password'],
            (bool) ($validated['remember'] ?? false)
        );

        if (!$ok) {
            $this->logger->log('Login gagal', 'Percobaan masuk dengan email tidak valid', ['email' => $validated['email']]);
            return back()
                ->withInput($request->only('email', 'remember'))
                ->with('error', 'Kredensial tidak valid.');
        }

        $this->logger->log('Login', 'Pengguna berhasil login', ['email' => $validated['email']]);
        return redirect()->intended('/');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logout();
        $this->logger->log('Logout', 'Pengguna keluar');

        return redirect('/login')->with('status', 'Anda telah keluar.');
    }
}

