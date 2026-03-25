<?php

namespace App\Providers;

use App\Services\Push\FcmPushSender;
use App\Services\Push\PushSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PushSender::class, FcmPushSender::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->environment('testing')) {
            $this->loadMigrationsFrom(base_path('tests/Support/Database/Migrations'));
        }
    }
}
