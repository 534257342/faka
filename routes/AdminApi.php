<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware('admin.operate_record')->group(function () {
    $path = 'Admin';
    Route::prefix('auth')->group(function () use ($path) {
        Route::get('navTree', $path . '\Admin\NavController@navTree');//资金流水
        Route::resource('role', $path . '\Auth\RoleController');//资金流水
    });
    Route::prefix('admin')->group(function () use ($path) {
        Route::resource('nav', $path . '\Admin\NavController');//资金流水
        Route::resource('record', $path . '\Admin\OperateRecordController');//自动添加-API
    });

    Route::prefix('common')->group(function () use ($path) {
        Route::resource('product', $path . '\Common\ProductController');
        Route::resource('banner', $path . '\Common\BannerController');
        Route::resource('pays', $path . '\Common\PaysController');
        Route::resource('type', $path . '\Common\TypeController');
        Route::resource('orders', $path . '\Common\OrdersController');
        Route::resource('user', $path . '\Common\UserController');
        Route::resource('code', $path . '\Common\CodeController');
        Route::resource('file', $path . '\Common\FileController');
        Route::post('uploadInfo', $path . '\Common\ExcelController@uploadInfo');
    });


    Route::prefix('user')->group(function () use ($path) {
        Route::resource('joins', $path . '\User\JoinsController');
        Route::resource('actionlog', $path . '\User\ActionLogController');
    });

    Route::prefix('system')->group(function () use ($path) {
        Route::resource('webset', $path . '\System\WebsetController');
        Route::resource('actionlog', $path . '\User\ActionLogController');
    });
});
