<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use \Illuminate\Support\Facades\Route;


Route::prefix('admin')->group(function(){
    $path = 'Admin';
    //后台登录
    Route::prefix('user')->group(function()use($path){

        Route::post('session',$path.'\User\SessionController@login');//登录
        Route::group(['middleware'=>'jwt.api.auth'],function()use($path){
            Route::put('session/password',$path.'\User\SessionController@updatePassword');//修改密码
            Route::post('session/register',$path.'\User\SessionController@register');//注册
            Route::post('session/logout',$path.'\User\SessionController@logout');//登出
        });
    });
});

$path = 'Api';
//前台登录注册
Route::prefix('user')->group(function()use($path){
    Route::post('send_sms', $path.'\Common\UserController@sendCode');//短信发送
    Route::post('send_voice_sms', $path.'\Common\UserController@sendVoiceCode');//语音短信发送
    Route::any('session',$path.'\User\SessionController@login');//登录
    //   Route::post('session/register',$path.'\User\SessionController@register');//注册
    Route::put('session/forget_password',$path.'\User\SessionController@forgetPwd');//忘记密码


    Route::group(['middleware'=>'jwt.api.auth'],function()use($path){
        Route::put('session/password',$path.'\User\SessionController@updatePassword');//修改密码
        Route::post('session/logout',$path.'\User\SessionController@logout');//登出
        // Route::any('session/call',$path.'\User\SessionController@callPhone');//打电话

        Route::get('hello',  function () {
            dd(1);
        });
    });
});


//api


//Route::post('upload/images/{type?}',$path.'\Common\UploadController@images');//图片上传
Route::post('upload/pics',$path.'\Common\UploadController@pics');//图片上传

Route::post('admin/upload/images', 'Admin\Common\UploadController@images');//后台图片上传




Route::post('common/pay/notify/{type}', $path.'\Common\PayController@notify');//支付回调相关

Route::get('common/code/verification_code', $path.'\Common\CodeController@verificationCode');//验证手机号和验证码是否匹配


Route::group(['middleware'=>'jwt.api.auth'],function(){
    include 'HomeApi.php'; //前台
    include 'AdminApi.php';//后台

});

Route::group(['middleware'=>'auth.user.open'],function(){
    include 'UserOpenApi.php';

    Route::resource('user/tag', 'Api\User\TagController');//自动添加-API


});

Route::prefix('system')->group(function()use($path){
    Route::resource('site', $path.'\System\SiteController');//自动添加-ADMIN
    Route::resource('config', $path.'\System\ConfigController');//自动添加-ADMIN
});


Route::resource('express/record', 'Api\Express\RecordController');//自动添加-API
Route::resource('system/setting', 'Api\System\SettingController');//自动添加-API
Route::resource('admin/system/setting', 'Admin\System\SettingController');//自动添加-ADMIN
