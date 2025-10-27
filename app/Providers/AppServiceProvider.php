<?php

namespace App\Providers;

use App\Services\ActivityLog\ActivityLogger;
use App\Services\ActivityLog\ActivityLoggerInterface;
use App\Services\Auth\AuthService;
use App\Services\Auth\AuthServiceInterface;
use App\Services\Report\ReportServiceInterface;
use App\Services\Settings\SettingsService;
use App\Services\Settings\SettingsServiceInterface;
use App\Services\Category\CategoryServiceInterface;
use App\Services\Category\CategoryService;
use App\Services\Product\ProductServiceInterface;
use App\Services\Product\ProductService;
use App\Services\User\UserServiceInterface;
use App\Services\User\UserService;
use App\Services\Cashier\CashierServiceInterface;
use App\Services\Cashier\CashierService;
use App\Services\Payments\MidtransServiceInterface;
use App\Services\Payments\MidtransService;
use App\Services\Report\ReportService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->singleton(SettingsServiceInterface::class, SettingsService::class);
        $this->app->singleton(CategoryServiceInterface::class, CategoryService::class);
        $this->app->singleton(ProductServiceInterface::class, ProductService::class);
        $this->app->singleton(UserServiceInterface::class, UserService::class);
        $this->app->singleton(CashierServiceInterface::class, CashierService::class);
        $this->app->singleton(MidtransServiceInterface::class, MidtransService::class);
        $this->app->bind(ReportServiceInterface::class, ReportService::class);
        $this->app->singleton(ActivityLoggerInterface::class, ActivityLogger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        
        // Share common settings with views, but don't allow failures (e.g., DB not ready) to break bootstrapping
        try {
            $settings = $this->app->make(SettingsServiceInterface::class);
            View::share('appStoreName', $settings->storeName());
            View::share('appCurrency', $settings->currency());
            View::share('appDiscountPercent', $settings->discountPercent());
            View::share('appTaxPercent', $settings->taxPercent());
            View::share('appStoreAddress', $settings->storeAddress());
            View::share('appStorePhone', $settings->storePhone());
            View::share('appStoreLogoPath', $settings->storeLogoPath());
            View::share('appReceiptFormat', $settings->receiptNumberFormat());
        } catch (\Throwable $e) {
            // Fallback safe defaults so composer install / package discovery won't fail
            View::share('appStoreName', config('app.name', 'POS'));
            View::share('appCurrency', 'IDR');
            View::share('appDiscountPercent', 0.0);
            View::share('appTaxPercent', 0.0);
            View::share('appStoreAddress', '');
            View::share('appStorePhone', '');
            View::share('appStoreLogoPath', null);
            View::share('appReceiptFormat', 'INV-{YYYY}{MM}{DD}-{SEQ:6}');
        }

        Blade::directive('money', function ($expression) {
            return "<?php
                \$__cur = app(\\App\\Services\\Settings\\SettingsServiceInterface::class)->currency();
                \$__code = is_string(\$__cur) ? strtoupper(\$__cur) : 'IDR';
                \$__prefix = \$__code === 'IDR' ? 'Rp ' : (\$__code . ' ');
                echo \$__prefix . number_format($expression, 0, ',', '.');
            ?>";
        });

        View::composer('layouts.header', function () {
            if (Auth::check()) {
                try {
                    app(\App\Services\Product\ProductAlertService::class)->scanAndNotify(7);
                } catch (\Throwable $e) {
                    // swallow to avoid blocking request
                }
            }
        });
    }
}
