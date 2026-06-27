<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\BusRepositoryInterface;
use App\Repositories\Eloquent\BusRepository;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Eloquent\BookingRepository;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Repositories\Eloquent\TripRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BusRepositoryInterface::class, BusRepository::class);
        $this->app->bind(BookingRepositoryInterface::class, BookingRepository::class);
        $this->app->bind(TripRepositoryInterface::class, TripRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}