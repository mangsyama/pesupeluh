<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Vite;
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
        date_default_timezone_set(config('app.timezone', 'Asia/Makassar'));

        Carbon::serializeUsing(function (\DateTimeInterface $date) {
            $tz = config('app.timezone', 'Asia/Makassar');
            return Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d H:i:s'), $tz)->format('Y-m-d\TH:i:sP');
        });

        Vite::prefetch(concurrency: 3);
    }
}
