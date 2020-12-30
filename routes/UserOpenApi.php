<?php
/**
 * Created by PhpStorm.
 * User: yons
 * Date: 2018/11/30
 * Time: 下午12:05
 */
use \Illuminate\Support\Facades\Route;
    
    Route::prefix('open')->group(function(){
        $path = 'Api';
        //  Route::post('session',$path.'\User\SessionController@login');//登录
        Route::prefix('express')->group(function()use($path){
            Route::post('order/pre_order',$path.'\Express\OrderController@preOrder');
            Route::post('batch_order',$path.'\Express\BatchOrderController@batch');//批量下单
            Route::resource('order', $path.'\Express\OrderController');//自动添加-ADMIN
            Route::resource('router', $path.'\Express\RouterController');//自动添加-ADMIN
            Route::delete('other_order/{other_number}',$path.'\Express\OrderController@otherCancelOrder');//第三方取消运单
            Route::delete('batch_cancel_order',$path.'\Express\OrderController@batchCancelOrder');
            Route::resource('area', $path.'\Express\AreaController');//自动添加-ADMIN
        });
        
        Route::prefix('address')->group(function()use($path){
            Route::resource('address', $path.'\Address\AddressController');//自动添加-ADMIN
        });
        Route::prefix('user')->group(function()use($path){
            Route::resource('coupon', $path.'\User\CouponController');//自动添加-ADMIN
        });
    });
