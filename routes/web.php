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
    $app->post('/getCaptcha', 'SmsController@sendCode');

    # ------------------ 登录注册 ------------------------
    $app->post('/register', 'LoginController@register');
    $app->post('/forget', 'PwdController@forgetPassword');
    $app->post('/login', 'LoginController@login');
    $app->post('/logout', 'LoginController@logout');

    # ------------------ 商品模块 ------------------------
    $app->post('/getProductList', 'ProductController@getProductList');

    # ------------------ 币价行情 ------------------------
    $app->post('/getAssetprice', 'AssetPriceController@getAssetprice');

    # ------------------ 全网算力 ------------------------
    $app->post('/getAssetstate', 'AssetStateController@getAssetstate');

    # ------------------ 公告查询 ------------------------
    $app->post('/getNotice', 'NoticeController@getNoticeList');
    $app->post('/getNoticeInfo', 'NoticeController@getNoticeInfo');

    # ------------------ 通知查询 ------------------------
    $app->post('/getNotify', 'NotifyController@getNotifyList');

    # ------------------ 上传 ------------------------
    $app->post('/upload', 'UploadController@index');

    $app->post('/getRate', 'ConfigController@getRate');

    $app->group(['middleware' => 'access'], function () use ($app) {
        # ------------------ 账密 ------------------------

        $app->post('/changePassword', 'PwdController@changePassword');
        $app->post('/capitalPassword', 'PwdController@capitalPassword');
        $app->post('/changeCapitalPassword', 'PwdController@changeCapitalPassword');
        $app->post('/checkCapitalPassword', 'PwdController@checkCapitalPassword');


        # ------------------ 个人中心 ------------------------
        $app->post('/getUserInfo', 'UserCenterController@getUserInfo');
        $app->post('/authentication', 'UserCenterController@authentication');
        $app->post('/getAuthInfo', 'UserCenterController@getAuthInfo');

        # ------------------ 商品模块 ------------------------
        $app->post('/getProductInfo', 'ProductController@getProductInfo');

        # ------------------ 订单模块 ------------------------
        $app->post('/addOrderInfo', 'OrderController@add');
        $app->post('/getOrderByUid', 'OrderController@getOrders');
        $app->post('/getOrderById', 'OrderController@getOrderInfo');
        $app->post('/orderCancel', 'OrderController@cancel');
        $app->post('/orderDelete', 'OrderController@delete');

        # ------------------ 支付 ------------------------
        $app->post('/payment', 'PayController@payment');

        # ------------------ 我的算力 ------------------------
        $app->post('/getPower', 'UserPowerController@getPower');

        # ------------------ 我的收益 ------------------------
        $app->post('/getMyProfit', 'UserProfitController@getMyProfit');
        $app->post('/getProfitList', 'UserProfitController@getProfitList');

        # ------------------ 我的钱包 ------------------------
        $app->post('/userAsset', 'UserAssetController@userAsset');
        $app->post('/getMyAsset', 'UserAssetController@getMyAsset');
        $app->post('/getTransferList', 'UserAssetController@getTransferList');
        $app->post('/transfer', 'UserAssetController@transfer');
//        $app->post('/recharge', 'UserAssetController@recharge');

        # ------------------ 提币 ------------------------
        $app->post('/coin', 'DrawCoinController@coin');
        $app->post('/getCoinList', 'DrawCoinController@getCoinList');

        # ------------------ link ------------------------
        $app->post('/link/sendTransaction', 'LinkServiceController@sendTransaction');
        $app->post('/link/getBalance', 'LinkServiceController@getBalance');
        $app->post('/link/privateWallter', 'LinkServiceController@privateWallter');
        $app->post('/link/mnemonicWallter', 'LinkServiceController@mnemonicWallter');
        $app->post('/link/getWalletValidateAddress', 'LinkServiceController@getWalletValidateAddress');
    });

});

