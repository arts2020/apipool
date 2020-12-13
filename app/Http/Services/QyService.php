<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 2017/8/30
 * Time: 上午10:04
 */

namespace App\Http\Services;


use App\Models\Base;

class QyService {

    private static function getToken(){
        $appid = 'wwc7bdeb41c632d7af';
        $secret = 'XdEvBHMgi22S2oyYtTTeegfkH9V-P9cT-TewFvVuKnE';
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=%s&corpsecret=%s';
        $url = sprintf($url,$appid,$secret);

        $base = new Base();
        $access_token = $base->get('access_token_'.$appid);
        if (!$access_token){
            //下面两行应该放在设置缓存位置
            $response = curlGet($url);
            $response = json_decode($response,true);

            if ($response['errcode'] == 0 && $response['errmsg'] == 'ok'){
                $access_token = $response['access_token'];
                $base->set('access_token_'.$appid,$access_token,3600);
            }
        }
        return $access_token;
    }

    public static function send($content,$touser,$getWxAccount = false){
        $access_token = self::getToken();

        if ($getWxAccount){
            $userapi = env('USER_API').'/admin/getWxAccount?ids='.$touser;
            $res = curlGet($userapi);
            $res = json_decode($res,true);
            if ($res['code'] == 200){
                $touser = $res['data'];
            }
        }

        $data = [];
        $data['touser'] = $touser;
        //$data['toparty']='';
        //$data['totag']='';
        $data['msgtype']='text';
        $data['agentid']= '1000002';
        $data['text']  = [
            'content'=>$content,
        ];
        $data['safe'] = "0" ;
        $data = json_encode($data);

        $url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=%s';
        $url = sprintf($url,$access_token);
        $rs = curlPost($url,$data);
        $rs = json_decode($rs,true);
        if ($rs['errcode'] == 0 && $rs['errmsg'] == "ok"){
            return true;
        }else{
            return false;
        }
    }
}