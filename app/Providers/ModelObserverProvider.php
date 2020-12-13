<?php

namespace App\Providers;

use App\Models\Order;
use Illuminate\Support\ServiceProvider;


class ModelObserverProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
//        Order::observe('App\Observers\OrderObserver');
    }

    public function register()
    {
        # code...
    }
}
