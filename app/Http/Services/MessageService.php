<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 17/8/21
 * Time: 上午9:32
 */
namespace App\Http\Services;

use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\Hospital;
use Illuminate\Support\Facades\Redis;

class MessageService
{

    /**
     * 添加站内信
     * @param type 1 用户 2护士 3系统
     */
    public function add($type, $type_id, $templateId, $data = [], $orderId = '', $hospitalId = '')
    {
        if (!$type) {
            return ['code' => 100, 'msg' => '缺少类型'];
        }

        if (!$templateId) {
            return ['code' => 102, 'msg' => '缺少模板Id'];
        }
        $messageTemplateModel = new MessageTemplate();
        $template = $messageTemplateModel->getInfo($templateId);
        $sms_content = $this->postTempReplace($template['content'], $data);
        if (!$sms_content) {
            return ['code' => 103, 'msg' => '消息内容为空'];
        }
        $messageModel = new Message();
        $messageModel->type = $type;
        if ($type != 3) {
            $messageModel->type_id = $type_id;
        }
        if ($template['name'] != '')
            $messageModel->sms_title = $template['name'];
        $messageModel->order_id = $orderId;
        $hospitalInfo = $this->getHospital($hospitalId);
        $messageModel->hospital_id = $hospitalId;
        $messageModel->sms_content = $hospitalInfo . $sms_content;
        $messageModel->created_at = time();
        $messageModel->save();

        return ['code' => 200, 'msg' => '提交成功'];

    }

    //模版的替换
    public function postTempReplace($tpl_content, $replace_content)
    {
        //计算短信模版中有多少需要替换的内容变量
        $replace = substr_count($tpl_content, '#');
        //计算几个变量
        $number = intval(floor($replace / 2));

        //重复，去除，分割数组(由内到外)
        $pattern = explode(',', rtrim(str_repeat("/#\w+#/,", $number), ','));

        //正则匹配
        $tpl_content = preg_replace($pattern, $replace_content, $tpl_content, 1);

        return $tpl_content;
    }


    //获取医院信息
    public function getHospital($hospitalId = '')
    {
        if ($hospitalId) {
            $key = md5('zpd_hospital' . $hospitalId);
            if(empty(Redis::get($key))){
                $userApi = env('USER_API');
                $hospital = curlGet($userApi.'/hospital/getInfo?id='.$hospitalId);
                $hospitalInfo = \GuzzleHttp\json_decode($hospital,true);
                Redis::setex($key, 5184000, json_encode($hospitalInfo['data']));
            }
            $hospital = json_decode(Redis::get($key));
        }
        $hospital_name = $hospital->hospital_name ? $hospital->hospital_name . ':' : '';
        return $hospital_name;
    }
}