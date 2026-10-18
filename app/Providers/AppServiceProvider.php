<?php

namespace App\Providers;

use App\Session\MinimalDatabaseSessionHandler;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
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
        $this->registerSessionHandler();
        $this->configureLoginRateLimit();
    }

    /**
     * Replace the "database" session driver so that SESSION_DRIVER=database
     * and config/session.php stay unchanged, but no IP address or user agent
     * is stored.
     */
    private function registerSessionHandler(): void
    {
        Session::extend('database', function (Application $app): MinimalDatabaseSessionHandler {
            $config = $app->make('config');

            return new MinimalDatabaseSessionHandler(
                $app->make('db')->connection($config->get('session.connection')),
                $config->get('session.table'),
                $config->get('session.lifetime'),
                $app,
            );
        });
    }

    /**
     * Limit for POST /auth/login (route middleware throttle:login):
     * at most 20 attempts per minute per IP address. The key is a SHA-256
     * hash so that the cache never holds the IP address in plain text.
     *
     * The second limit (5 failed attempts per email and IP address, reset by
     * a successful login) lives in App\Http\Requests\Auth\LoginRequest,
     * because it has to count failures only and be cleared on success.
     */
    private function configureLoginRateLimit(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(20)
            ->by('ip:'.hash('sha256', (string) $request->ip())));
    }
}
