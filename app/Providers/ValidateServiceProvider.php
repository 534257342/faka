<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class ValidateServiceProvider extends ServiceProvider
{
    protected $cellphoneRegex = '/^1[3456789][0-9]{9}$/';

    protected $telephoneRegex = '/(^(0[0-9]{2}-?)([2-9][0-9]{7})(-[0-9]{1,4})?$)|(^(0[0-9]{3}-?)([2-9][0-9]{6})(-[0-9]{1,4})?$)|(^400(-?[0-9]{3,4}){2}$)/';

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->cellphone();

        $this->telephone();

        $this->phone();
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    protected function cellphone()
    {
        Validator::extend('cellphone', function ($attribute, $value, $parameters, $validator)
        {
            return !! preg_match($this->cellphoneRegex, $value);
        });

        Validator::replacer('cellphone', function ($message, $attribute, $rule, $parameters)
        {
            return $attribute . '必须是一个手机号码格式';
        });
    }

    protected function telephone()
    {
        Validator::extend('telephone', function ($attribute, $value, $parameters, $validator)
        {
        });

        Validator::replacer('telephone', function ($message, $attribute, $rule, $parameters)
        {
            return $attribute . '必须是一个座机号码或400电话格式，座机允许分机号';
        });
    }

    protected function phone()
    {
        Validator::extend('phone', function ($attribute, $value, $parameters, $validator)
        {
            if (preg_match($this->cellphoneRegex, $value))
            {
                return true;
            }

            return !! preg_match($this->telephoneRegex, $value);
        });

        Validator::replacer('phone', function ($message, $attribute, $rule, $parameters)
        {
            return $attribute . '必须是一个正确的手机或座机号码格式，座机允许分机号';
        });
    }
}
