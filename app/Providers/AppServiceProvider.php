<?php

namespace App\Providers;

use App\Support\App\Push\FcmPushSender;
use App\Support\App\Push\PushSender;
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
        //
    }
}
