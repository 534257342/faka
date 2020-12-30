<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        if ($this->app->environment() == 'local') {
            \DB::listen(
                function ($query) {
                    $tmp = str_replace('?', '"'.'%s'.'"', $query->sql);
                    $time = $query->time;
                    if($time>10){ //10ms以上
                        $qBindings = [];
                        foreach ($query->bindings as $key => $value) {
                            if (is_numeric($key)) {
                                $qBindings[] = $value;
                            } else {
                                $tmp = str_replace(':'.$key, '"'.$value.'"', $tmp);
                            }
                        }
                        $tmp = vsprintf($tmp, $qBindings);
                        $tmp = str_replace("\\", "", $tmp);
                        \Log::info(' execution time: '.$time.'ms; '.$tmp."\n\n\t");
                    }

                }
            );
        }
    }
}
