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
            return Carbon::instance($date)
                ->setTimezone(config('app.timezone', 'Asia/Makassar'))
                ->format('Y-m-d\TH:i:sP');
        });

        Vite::prefetch(concurrency: 3);
    }
}
