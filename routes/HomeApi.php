<?php

Route::any('/pushTrace','Api\Auth\RoleController@pushTrace');
//Route::get('/mytest','Api\Auth\RoleController@test');
$path = 'Api';
Route::prefix('common')->group(function()use($path){

    Route::get('getUserApply', $path.'\Common\UserController@getUserApply');//自动添加-ADMIN
    Route::resource('pay', $path.'\Common\PayController');//自动添加-ADMIN
    Route::resource('user', $path.'\Common\UserController');//自动添加-ADMIN
    Route::resource('code', $path.'\Common\CodeController');//自动添加-ADMIN
    Route::resource('apply', $path.'\Common\ApplyController');//自动添加-ADMIN


    Route::resource('type', $path.'\Common\TypeController');//自动添加-ADMIN
    Route::get('oneOrderInfo/{id}', $path.'\Common\UserController@oneOrderInfo');//自动添加-ADMIN\

});

Route::prefix('express')->group(function()use($path){
    Route::resource('info', $path.'\Express\InfoController');//自动添加-ADMIN
    Route::get('customActive', $path.'\Express\InfoController@customActive');//自动添加-ADMIN
});

Route::prefix('test')->group(function()use($path){
    Route::resource('test_detail', $path.'\Test\TestDetailController');//自动添加-ADMIN
    Route::resource('test', $path.'\Test\TestController');//自动添加-ADMIN
});











