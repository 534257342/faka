<?php

namespace App\Providers;

use App\Support\ElasticService;
use Illuminate\Support\ServiceProvider;

class ElasticServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Elastic', function () {
            return new ElasticService();
        });
    }

}
