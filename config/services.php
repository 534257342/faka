<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\Models\Common\CommonUser::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'addressFence' => [//地址围栏
        'lngMin' => env('ADDRESS_LNGMIN'),//最小经度
        'lngMax' => env('ADDRESS_LNGMAX'),//最大经度
        'latMin' => env('ADDRESS_LATMIN'),//最小纬度
        'latMax' => env('ADDRESS_LATMAX'),//最大纬度
    ],

    'map' => [
        'GaoKey' => env('GAO_API_KEY', ''),
        'BaiKey' => env('BAI_API_KEY', ''),
        'key' => env('EAGLE_API_KEY', ''),
    ],
    'sms'=>[
        'url'=>env('SMS_URL',''),
        'voice_url'=>env('SMS_VOICE_URL',''),
        'account'=>env('SMS_ACCOUNT',''),
        'password'=>env('SMS_PASSWORD',''),
        'sign'=>env('SMS_SIGN',''),
        'subCode'=>env('SMS_SUBCODE',''),
    ],
    'cancelOrder'=>[
        'url'=>env('CANCEL_ORDER_URL',''),
    ]

];
