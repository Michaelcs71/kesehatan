<?php

namespace App\Providers;

use App\Services\Whatsapp\CloudApiWhatsAppSender;
use App\Services\Whatsapp\LogWhatsAppSender;
use App\Services\Whatsapp\WhatsAppSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WhatsAppSender::class, function () {
            return match (config('pengingat.whatsapp.driver')) {
                'cloud_api' => new CloudApiWhatsAppSender,
                default => new LogWhatsAppSender,
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
