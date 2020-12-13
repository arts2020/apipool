<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Services\SmsService;
use Illuminate\Http\Request;


class SmsController extends ApiController
{

    /**
     * @SWG\Post(path="/onGetCaptcha",
     *   tags={"onGetCaptcha"},
     *   summary="发送验证码",
     *   description="",
     *   operationId="",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="phonenumber",
     *     in="query",
     *     description="手机号",
     *     required=true,
     *     type="string",
     *     default=""
     *   ),
     *   @SWG\Parameter(
     *     name="smstype",
     *     in="query",
     *     description="发送类型",
     *     required=true,
     *     type="integer",
     *     default="1"
     *   ),
     *   @SWG\Response(response=200, description="发送成功", @SWG\Schema(ref="#/definitions/SysSmsLog"))
     *
     * )
     */
    public function sendCode(Request $request)
    {
        $phone = $request->input('phonenumber');
        $sendType = $request->input('smstype');

        $smsLogic = new SmsService();
        $msg = $smsLogic->sendCode($phone, $sendType);

        return $this->apiReturn($msg);
    }
}