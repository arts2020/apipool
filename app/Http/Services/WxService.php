<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 2018/10/31
 * Time: 上午10:06
 */

namespace App\Http\Services;


use Illuminate\Support\Facades\Redis;

class WxService
{
    public $redis;

    public function __construct()
    {
        $this->redis = Redis::connection('default');
    }

    /**
     * @todo 获取token
     */
    public function getToken(){

        $token = $this->redis->get("wx_token");

        if($token){
            return $token;
        }

        //$wechat = config("wechatDev");
        $dev = env("APP_ENV");
        if($dev == "local"){
            $wechat = config("wechatDev");
        }else{
            $wechat = config("wechat");
        }

        $appid = $wechat['dev']['app_id'];
        $wx_appsecret = $wechat["dev"]['secret'];

        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$wx_appsecret;

        $res = curlGet($url);
        $res = json_decode($res , true);
        $token = $res["access_token"];

        $this->redis->set("wx_token", $token);
        $this->redis->expire("wx_token", 7000);

        return $token;
    }

    /**
     * 发送模板消息
     *
     */
    public function sendTemplate($touser, $template_title, $data=[], $url=''){
        return;
        if(!$touser){
            return false;
        }
        $env = env('APP_ENV');
        if($env != 'local'){
            $templates = config('wxTemplate.production');
        }else{
            $templates = config('wxTemplate.local');
        }

        $template_id = $templates[$template_title];
        if(!$template_id){
            return false;
        }
        $access_token = $this->getToken();
        if(!$access_token){
            return false;
        }
        $industry_url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=%s";
        $industry_url = sprintf($industry_url,$access_token);
        $template = [];
        $template['touser'] = $touser;
        $template['template_id'] = $template_id;
        $template['url'] = $url;
        $template['data'] = $data;

        $response = curlPost($industry_url,urldecode(json_encode($template)));
        $response = json_decode($response,true);

        return $response;
    }
}