<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;


class Controller extends BaseController
{

    public function isGet()
    {
        return $_SERVER['REQUEST_METHOD']=="GET" ? true : false ;
    }

    public function isPost()
    {
        return $_SERVER['REQUEST_METHOD']=="POST" ? true : false ;
    }

    public function isAjax()
    {
        return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest" ? true : false ;
    }

    /**
     * 传变量到视图
     * @param $key
     * @param $value
     */
    public function assign($key,$value){
        view()->share($key,$value);
    }

    /**
     * @author alan
     * @date 2017-05-02
     * @param $msg
     * @param $type
     * @return mixed
     */
    public function apiReturn($msg= ['code' => 200, 'msg'  => '操作成功', 'data' => ''], $type='json')
    {
        switch($type){
            case 'json':
                return response()->json($msg);
                break;
            case 'jsonp':
                $request = new Request();
                return response()->json($msg)
                    ->setCallback($request->input('callback'));
                break;

            default:
                return response()->json($msg);
        }
    }

    /**
     * @author alan
     * @date 2017-05-02
     * @param $url
     * @param $params
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect($url='/', $params=[])
    {
        $url = env('APP_URL').$url ;
        return redirect($url)->with($params);
    }
}
