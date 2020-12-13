<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 2019-03-07
 * Time: 16:04
 */

namespace App\Http\Services;

use App\Models\MemberPatient;
use App\Models\ZhuyuanBasicInfoNew;

class MemberPatientService
{
    public static function formatKeshi(&$input,$user_id,$hospital_id){
        //获取注册科室
        $url = env('APP_USER_URL').'/bmember/getInfoByMId?member_id='.$user_id;
        $rs = curlGet($url);
        $rs = json_decode($rs,true);
        if ($rs['code'] == 200){
            $input['register_keshi_id'] = $rs['data']['keshi_id'];
        }

        //设置his科室
//        $zhuyuanBasicInfoNewModel = new ZhuyuanBasicInfoNew();
//        $zhuyuanBasic = $zhuyuanBasicInfoNewModel->where(['shenfenzheng_hao'=>$input['patient_id_card']])->first();
//        if ($zhuyuanBasic){
//            //接口读取his科室绑定
//            $url = env('APP_USER_URL').'/hospital/getHisKeshiBind?his_keshi_name='.$zhuyuanBasic['zhuyuan_bingqu'].'&hospital_id='.$hospital_id;
//            $rs = curlGet($url);
//            $rs = json_decode($rs,true);
//            if ($rs['code'] == 200 && !empty($rs['data'])){
//                $input['his_bind_keshi_id'] = $rs['data']['keshi_id'];
//                $input['his_zhuyuan_id'] = $zhuyuanBasic['zhuyuan_id'];
//            }
//        }else{
            $input['his_bind_keshi_id'] = 0;
            $input['his_zhuyuan_id'] = 0;
//        }
    }

    /**
     * 处理百度人脸识别
     * @param $paitent
     * @return mixed
     */
    public static function uploadBaidu($paitent, $id){
        $patientModel = new MemberPatient();
        $hospital_id = $paitent['hospital_id'];
        $client = new \AipFace(env('BAIDU_APP_ID'), env('BAIDU_API_KEY'), env('BAIDU_SECRET_KEY'));
        $imageType = "BASE64";

        if ($paitent['head_pic'] == ''){
            //删除人脸库
            $client->deleteUser($hospital_id,$paitent['id']);
            return true;
        }

        if ($id){
            $info = $patientModel->where('id',$id)->where('is_del',1)->first();
            if (isset($info['id']) && $info['id']){
                $image_data = file_get_contents($paitent['head_pic']);
                $base64_image = base64_encode($image_data);
                $detectResult = $client->detect($base64_image,'BASE64');
                if ($detectResult['error_code'] == 0) {
                    $userResult = $client->getUser($info['id'],$hospital_id);
                    if ($userResult['error_code'] > 0){
                        $updateUserResult = $client->addUser($base64_image, $imageType, $hospital_id, $id);
                    }else{
                        $updateUserResult = $client->updateUser($base64_image, $imageType, $hospital_id, $id);
                    }
                    if ($updateUserResult['error_code'] == 0 && $updateUserResult['error_msg'] == 'SUCCESS'){

                        $patientModel->where('id',$id)->update(['head_pic_token'=>$updateUserResult['result']['face_token']]);
                    }else{
                        return ['code'=>$updateUserResult['error_code'],'msg'=>'图片检测错误'];
                    }
                }else{
                    return ['code'=>$detectResult['error_code'],'msg'=>'图片检测错误'];
                }
            }
        }else{
            $id = $paitent['id'];

            $image_data = file_get_contents($paitent['head_pic']);
            $base64_image = base64_encode($image_data);
            $detectResult = $client->detect($base64_image,'BASE64');
            if ($detectResult['error_code'] == 0) {
                $addUserResult = $client->addUser($base64_image, $imageType, $hospital_id, $id);
                if ($addUserResult['error_code'] == 0 && $addUserResult['error_msg'] == 'SUCCESS') {
                    $patientModel->where('id', $id)->update(['head_pic_token' => $addUserResult['result']['face_token']]);
                } else {
                    return ['code'=>$addUserResult['error_code'],'msg'=>'图片上传错误'];
                }
            }else{
                return ['code'=>$detectResult['error_code'],'msg'=>'图片检测错误'];
            }
        }
    }

}