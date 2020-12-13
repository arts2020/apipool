<?php
namespace App\Http\Services;

use App\Models\LoginLog;
use App\Models\Member;
use App\Models\SysSmsLog;

class MemberService {
    /**
     * 手机短信登录
     */
    public function smsLogin($phone, $verify, $openId=''){
        if($phone == ''){
            return ['code'=>100,'msg'=>'手机号不能为空'];
        }
        if($verify == ''){
            return ['code'=>100,'msg'=>'验证码不能为空'];
        }
        $memberModel = new Member();
        $memberInfo = $memberModel->selectOne(['phone'=>$phone,'hospital_id'=>$hospital['id']]);
        if($memberInfo['status'] == 2){
            return ['code'=>100,'msg'=>'该账号已被禁用,请联系客服'];
        }
        $smsLogModel = new SysSmsLog();
        $sms = $smsLogModel->selectOne(['phone'=>$phone,'verify_code'=>$verify,'sms_type'=>1,'type'=>1]);
        if(empty($sms))
            return ['code'=>100,'msg'=>'验证码错误'];

        //如果用户不存在,则生成一个用户
        if(empty($memberInfo)){
            $memberModel->username = '用户'.generate_username(6);
            $memberModel->phone = $phone;
            $memberModel->hospital_id = $hospital['id'];
            $memberModel->status = 1;
            $memberModel->created_at = time();
            $memberModel->save();
            $memberInfo = $memberModel->selectOne(['phone'=>$phone]);
        }

        if(floor((time() - $sms['return_time'])/60) > 5){
            return ['code'=>100,'msg'=>'验证码超时'];
        }
        if($verify == $sms['verify_code']){
            //创建新用户、记录登录日志

            $loginLogModel = new LoginLog();
            $loginLogModel->type = 1;
            $loginLogModel->type_id = $memberInfo['id'];
            $loginLogModel->ip = ip2long(get_client_ip());
            $loginLogModel->remark = $phone.'账号登录系统';
            $loginLogModel->created_at = time();
            $loginLogModel->login_time = time();
            $loginLogModel->save();

            $token = genToken();
            $sessionData = [];
            $sessionData['userId'] = $memberInfo['id'];
            $sessionData['userType'] = 1;
            $sessionData['hospitalId'] = $hospital['id'];
            $memberModel->set($token, $sessionData);

            if($openId){
                //更新wechatUser
                $wechatUser = new WechatUser();
                $wechatUser->selectOne(['hospital_id'=>$hospital['id'],'open_id'=>$openId]);
                if($wechatUser['member_id'] == 0){
                    $wechatUser = new WechatUser();
                    $wechatUser->where(['hospital_id'=>$hospital['id'],'open_id'=>$openId])->update(['member_id'=>$memberInfo['id']]);
                }
            }

            return ['code'=>200,'msg'=>'登录成功', 'data'=> [
                'id'    => $memberInfo['id'],
                'token' => $token
            ]];
        }else{
            return ['code'=>100,'msg'=>'验证码输入错误'];
        }
    }

    /**
     * 手机短信登录
     */
    public function passwordLogin($phone, $password, $tag, $openId=''){
        if($phone == ''){
            return ['code'=>100,'msg'=>'手机号不能为空'];
        }
        if($password == ''){
            return ['code'=>100,'msg'=>'密码码不能为空'];
        }

        $memberModel = new Member();
        $memberInfo = $memberModel->selectOne(['phone'=>$phone,'hospital_id'=>$hospital['id']]);
        if($memberInfo['status'] == 2){
            return ['code'=>100,'msg'=>'该账号已被禁用,请联系客服'];
        }
        //如果用户不存在,则生成一个用户
        if(empty($memberInfo)){
            $memberModel->username = '用户'.generate_username(6);
            $memberModel->phone = $phone;
            $memberModel->hospital_id = $hospital['id'];
            $memberModel->status = 1;
            $memberModel->created_at = time();
            $memberModel->save();
            $memberInfo = $memberModel->selectOne(['phone'=>$phone]);
        }

        if($password == '123'){
            //记录登录日志
            $loginLogModel = new LoginLog();
            $loginLogModel->type = 1;
            $loginLogModel->type_id = $memberInfo['id'];
            $loginLogModel->ip = ip2long(get_client_ip());
            $loginLogModel->remark = $phone.'账号登录系统';
            $loginLogModel->created_at = time();
            $loginLogModel->login_time = time();
            $loginLogModel->save();

            $token = genToken();
            $sessionData = [];
            $sessionData['userId'] = $memberInfo['id'];
            $sessionData['userType'] = 1;
            $sessionData['hospitalId'] = $hospital['id'];
            $memberModel->set($token, $sessionData);

            if($openId){
                //更新wechatUser
                $wechatUser = new WechatUser();
                $wechatUser->selectOne(['hospital_id'=>$hospital['id'],'open_id'=>$openId]);
                if($wechatUser['member_id'] == 0){
                    $wechatUser = new WechatUser();
                    $wechatUser->where(['hospital_id'=>$hospital['id'],'open_id'=>$openId])->update(['member_id'=>$memberInfo['id']]);
                }
            }

            return ['code'=>200,'msg'=>'登录成功', 'data'=> [
                'id'    => $memberInfo['id'],
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
        $memberModel = new Member();
        $memberModel->del($token) ;
        return ['code'=>200,'msg'=>'登出成功'];
    }

}