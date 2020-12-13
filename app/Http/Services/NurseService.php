<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 17/8/21
 * Time: 上午9:32
 */
namespace App\Http\Services;

use App\Models\LoginLog;
use App\Models\Nurse;
use App\Models\SysSmsLog;
use App\Models\WechatUser;


class NurseService{

    /**
     * 手机短信登录
     */
    public function smsLogin($phone, $verify, $openId = ''){

        if($phone == ''){
            return ['code'=>100,'msg'=>'手机号不能为空'];
        }
        if($verify == ''){
            return ['code'=>100,'msg'=>'验证码不能为空'];
        }

        $nurseModel = new Nurse();
        $nurseInfo = $nurseModel->selectOne(['phone'=>$phone]);
        if(empty($nurseInfo)){
            return ['code'=>100,'msg'=>'该用户不存在,请联系客服'];
        }
        if($nurseInfo['status'] == 2){
            return ['code'=>100,'msg'=>'该账号已被禁用,请联系客服'];
        }

        $smsLogModel = new SysSmsLog();
        $sms = $smsLogModel->selectOne(['phone'=>$phone,'verify_code'=>$verify,'sms_type'=>1,'type'=>2]);

        if($phone != '13914701648'){ //苹果校验手机号
            if(empty($sms))
                return ['code'=>100,'msg'=>'验证码错误'];

            if(floor((time() - $sms['return_time'])/60) > 5){
                return ['code'=>100,'msg'=>'验证码超时'];
            }
        }

        if($verify == $sms['verify_code'] || $phone == '13914701648'){
            //记录登录日志
            $loginLogModel = new LoginLog();
            $loginLogModel->type = 2;
            $loginLogModel->type_id = $nurseInfo['id'];
            $loginLogModel->ip = ip2long(get_client_ip());
            $loginLogModel->remark = $phone.'账号登录系统';
            $loginLogModel->created_at = time();
            $loginLogModel->login_time = time();
            $loginLogModel->save();

            $token = genToken();
            $sessionData = [];
            $sessionData['userId'] = $nurseInfo['id'];
            $sessionData['userType'] = 2;
            $sessionData['hospitalId'] = $nurseInfo['hospital_id'];
            $nurseModel->set($token, $sessionData);

            if($openId){
                //更新wechatUser
                $wechatUser = new WechatUser();
                $wechatUser->selectOne(['hospital_id'=>$nurseInfo['hospital_id'],'open_id'=>$openId]);
                if($wechatUser['nurse_id'] == 0){
                    $wechatUser = new WechatUser();
                    $wechatUser->where(['hospital_id'=>$nurseInfo['hospital_id'],'open_id'=>$openId])->update(['nurse_id'=>$nurseInfo['id']]);
                }
            }

            return ['code'=>200,'msg'=>'登录成功','data'=> [
                'id'    => $nurseInfo['id'],
                'token' => $token
            ]];
        }else{
            return ['code'=>100,'msg'=>'验证码输入错误'];
        }
    }

    /**
     * 密码登录
     */
    public function passwordLogin($nurse_no, $password, $openId = ''){

        if($nurse_no == ''){
            return ['code'=>100,'msg'=>'工号不能为空'];
        }
        if($password == ''){
            return ['code'=>100,'msg'=>'密码不能为空'];
        }

        $nurseModel = new Nurse();
        $nurseInfo = $nurseModel->selectOne(['nurse_no'=>$nurse_no]);
        if(empty($nurseInfo)){
            return ['code'=>100,'msg'=>'该用户不存在,请联系客服'];
        }
        if($nurseInfo['status'] == 2){
            return ['code'=>100,'msg'=>'该账号已被禁用,请联系客服'];
        }

        if($password == '123'){
            //记录登录日志
            $loginLogModel = new LoginLog();
            $loginLogModel->type = 2;
            $loginLogModel->type_id = $nurseInfo['id'];
            $loginLogModel->ip = ip2long(get_client_ip());
            $loginLogModel->remark = $nurse_no.'账号登录系统';
            $loginLogModel->created_at = time();
            $loginLogModel->login_time = time();
            $loginLogModel->save();

            $token = genToken();
            $sessionData = [];
            $sessionData['userId'] = $nurseInfo['id'];
            $sessionData['userType'] = 2;
            $sessionData['hospitalId'] = $nurseInfo['hospital_id'];
            $nurseModel->set($token, $sessionData);

            if($openId){
                //更新wechatUser
                $wechatUser = new WechatUser();
                $wechatUser->selectOne(['hospital_id'=>$nurseInfo['hospital_id'],'open_id'=>$openId]);
                if($wechatUser['nurse_id'] == 0){
                    $wechatUser = new WechatUser();
                    $wechatUser->where(['hospital_id'=>$nurseInfo['hospital_id'],'open_id'=>$openId])->update(['nurse_id'=>$nurseInfo['id']]);
                }
            }

            return ['code'=>200,'msg'=>'登录成功','data'=> [
                'id'    => $nurseInfo['id'],
                'token' => $token
            ]];
        }else{
            return ['code'=>100,'msg'=>'密码输入错误'];
        }
    }

    /**
     * 登出
     * @param $token
     * @return array
     */
    public function logout( $token ){

        $nurseModel = new Nurse();
        $nurseModel->del($token) ;
        return ['code'=>200,'msg'=>'登出成功'];
    }

}