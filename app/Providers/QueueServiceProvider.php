<?php

namespace App\Providers;

use App\Support\QueueService;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Queue', function () {
            return new QueueService();
        });
    }

}
