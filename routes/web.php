<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

$pool = [
    'prefix' => '/poolapi/v1',
    'namespace' => 'V1',
//    'middleware' => ['auth'],
];


$app->group($pool, function () use ($app) {

    # ------------------ 短信验证 ------------------------
    $app->post('/onGetCaptcha', 'SmsController@sendCode');

    # ------------------ 登录注册 ------------------------
    $app->post('/onRegister', 'LoginController@register');
    $app->post('/onLogin', 'LoginController@login');
    $app->post('/onLogout', 'LoginController@logout');

    # ------------------ 商品模块 ------------------------




    $app->group(['middleware' => 'access'], function () use ($app) {

        # ------------------ 账密 ------------------------
        $app->post('/onForget', 'PwdController@forgetPassword');
        $app->post('/ChangePassword', 'PwdController@changePassword');
        $app->post('/CapitalPassword', 'PwdController@capitalPassword');
        $app->post('/ChangeCapitalPassword', 'PwdController@changeCapitalPassword');


        # ------------------ 个人中心 ------------------------
        $app->post('/GetUserInfo', 'UserCenterController@getUserInfo');
        $app->post('/Authentication', 'UserCenterController@authentication');


        # ------------------ 订单模块 ------------------------



    });

});

