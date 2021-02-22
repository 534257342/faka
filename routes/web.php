<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('robot', 'WeChatController@getRobot');//查询微信自定义菜单

Route::get('excel',  function () {
    return view('test.excel1');
});


//微信验证.
Route::group(['middleware' => ['web', 'wechat.oauth:snsapi_userinfo']], function () {
    Route::any('/get_param', 'WeChatController@getParam');
});

Route::prefix('wechat')->group(function () {
    Route::post('add_menu', 'WeChatController@addMenu');//添加微信自定义菜单
    Route::get('get_menu', 'WeChatController@getMenu');//查询微信自定义菜单
    Route::post('serve', 'WeChatController@serve'); //微信二维码推送消息
    Route::get('serve', 'WeChatController@serve');//微信二维码
    Route::get('qr', 'WeChatController@weChatQr'); //获取微信二维码
    Route::post('weAppLogin', 'WeChatController@weAppLogin'); //获取微信二维码
});



Route::get('api/admin/swagger/json',  'Admin\SwaggerController@getJson');//swagger定义
Route::get('api/swagger/json',  'Api\SwaggerController@getJson');//swagger定义
Route::get('api/driver/swagger/json',  'Driver\SwaggerController@getJson');//swagger定义
Route::get('api/vrp/swagger/json',  'Vrp\SwaggerController@getJson');//swagger定义

