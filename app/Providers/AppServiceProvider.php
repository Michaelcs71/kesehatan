<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\Whatsapp\WhatsAppSender::class, function () {
            return match (config('pengingat.whatsapp.driver')) {
                'cloud_api' => new \App\Services\Whatsapp\CloudApiWhatsAppSender(),
                default     => new \App\Services\Whatsapp\LogWhatsAppSender(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
