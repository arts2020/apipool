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
        if ($phone == '') {
            return ['code' => 100, 'msg' => '手机号不能为空'];
        }

        $random = getRandom();
        //调用接口发送短信
        $client = new Client($this->sms_config['sms']);
        $sendSms = new SendSms;
        $sendSms->setPhoneNumbers($phone);
        $sendSms->setSignName($this->sms_config['sign']);
        $sendSms->setTemplateCode($this->sms_config[$sendType]['code']);
        $sendSms->setTemplateParam(['code' => $random]);
        $sendSms->setOutId('');
        $result = $client->execute($sendSms);
        $response = objToArr($result);

        if ($response['Code'] == 'OK' && $response['Message'] == 'OK') {
            //记录短信日志
            $log = [
                'sms_type' => $sendType,
                'phone' => $phone,
                'verify_code' => $random,
                'return_data' => \GuzzleHttp\json_encode($response),
                'send_at' => now(),
                'return_at' => now(),
            ];
            $this->smsLogRep->store($log);
            return ['code' => 200, 'msg' => $response['Message']];
        } else {
//            return ['code' => 102, 'msg' => $response['Message']];
            return ['code' => 102, 'msg' => '验证码获取失败，请稍后再试'];
        }

    }
}