<?php

namespace App\Providers;

use App\Mail\ResendHttpTransport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Mail::extend('resend-http', fn () => new ResendHttpTransport((string) config('services.resend.key')));

        // Format tanggal berbahasa Indonesia ("14 Okt 2026").
        Carbon::setLocale('id');

        // Paksa HTTPS di production supaya form & asset tidak terblokir mixed-content.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
