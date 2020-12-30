<?php

namespace App\Providers;

use App\Support\WtpApi;
use Illuminate\Support\ServiceProvider;

class WtpServiceProvider extends ServiceProvider
{



    /**
     * Register any events for your application.
     *
     * @return void
     */



    public function register()
    {
        $this->app->singleton('Wpt', function () {
            return new WtpApi();
        });
    }
}
