<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            $frontend = config('app.frontend_url', 'http://localhost:5173');

            return $frontend . "/auth/reset-password?token=" . $token . "&email=" . urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
