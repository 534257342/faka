<?php

Route::prefix('admin')->middleware('admin.operate_record')->group(function () {
    $path = 'Admin';
    Route::prefix('common')->group(function () use ($path) {
        Route::post('/changePrice', $path . '\Common\UserController@changePrice');
        Route::resource('apply', $path . '\Common\ApplyController');//申请相关
        Route::resource('user', $path . '\Common\UserController');//用户相关
        Route::resource('code', $path . '\Common\CodeController');//验证码
        Route::resource('file', $path . '\Common\FileController');// 文件
        Route::post('uploadInfo', $path . '\Common\ExcelController@uploadInfo');// 后台上传excel解析数据
    });

    Route::prefix('money')->group(function () use ($path) {
        Route::resource('record', $path . '\Money\RecordController');//资金流水

    });
    Route::prefix('express')->group(function () use ($path) {
        Route::resource('info', $path . '\Express\InfoController');//所有数据

    });


    Route::prefix('test')->group(function () use ($path) {
        Route::resource('test_detail', $path . '\Test\TestDetailController');//自动添加-ADMIN
        Route::resource('test', $path . '\Test\TestController');//自动添加-ADMIN
    });


    Route::prefix('user')->group(function () use ($path) {
        Route::resource('actionlog', $path . '\User\ActionLogController');//自动添加-ADMIN
    });

});
