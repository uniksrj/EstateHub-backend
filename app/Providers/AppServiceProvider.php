<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth.login', function (Request $request) {
            return Limit::perMinute(5)->by($this->rateLimitKey($request, 'email'));
        });

        RateLimiter::for('auth.register', function (Request $request) {
            return Limit::perMinute(3)->by($this->rateLimitKey($request, 'email'));
        });

        RateLimiter::for('auth.otp', function (Request $request) {
            return Limit::perMinute(5)->by($this->rateLimitKey($request, 'email'));
        });

        RateLimiter::for('auth.password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($this->rateLimitKey($request, 'email'));
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            $frontend = config('app.frontend_url', 'http://localhost:5173');

            return $frontend . "/auth/reset-password?token=" . $token . "&email=" . urlencode($notifiable->getEmailForPasswordReset());
        });
    }

    private function rateLimitKey(Request $request, string $field): string
    {
        $value = strtolower((string) $request->input($field, ''));

        return $value !== ''
            ? $value . '|' . $request->ip()
            : $request->ip();
    }
}
