<?php

namespace App\Http\Services;

use Flc\Dysms\Client;
use Flc\Dysms\Request\SendSms;
use App\Repositories\SmsLogRepository;

class SmsService
{
    private $sms_config;

    public function __construct(SmsLogRepository $smsLogRepository)
    {
        $this->sms_config = config('sms');
        $this->smsLogRep = $smsLogRepository;
    }

    /**
     * 发送短息
     */
    public function sendCode($phone, $sendType)
    {
        $key = 'hp';
        if ($phone == '') {
            return ['code' => 100, 'msg' => '手机号不能为空'];
        }
        $rs = curlPost($this->sms_config['smsurl'],compact('key','phone','sendType'));
        $response = \GuzzleHttp\json_decode($rs, true);
        if ($response['Code'] == 'OK' && $response['Message'] == 'OK') {
            //记录短信日志
            $log = [
                'sms_type' => $sendType,
                'phone' => $phone,
                'verify_code' => $response['random'],
                'return_data' => \GuzzleHttp\json_encode($response),
                'send_at' => now(),
                'return_at' => now(),
            ];
            $this->smsLogRep->store($log);
            return ['code' => 200, 'msg' => $response['Message']];
        } else {
            return ['code' => 102, 'msg' => '验证码获取失败，请稍后再试'];
        }

    }
}