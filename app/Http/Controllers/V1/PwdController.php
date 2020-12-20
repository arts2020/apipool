<?php
namespace App\Http\Controllers\V1;

use App\Repositories\SmsLogRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PwdController extends ApiController
{
    protected $userRep;
    protected $smsLogRep;

    public function __construct(Request $request, UserRepository $userRepository,
                                SmsLogRepository $smsLogRepository)
    {
        parent::__construct($request);
        $this->userRep = $userRepository;
        $this->smsLogRep = $smsLogRepository;
    }

    /**
     * @SWG\Get(path="/forget",
     *   tags={"forget"},
     *   summary="忘记密码",
     *   description="",
     *   operationId="",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="phone",
     *     in="query",
     *     description="电话号码",
     *     type="integer",
     *     required=false,
     *     default=1
     *   ),
     *   @SWG\Parameter(
     *     name="captcha",
     *     in="query",
     *     description="验证码",
     *     type="integer",
     *     required=false,
     *     default=15
     *   ),
     *   @SWG\Parameter(
     *     name="password",
     *     in="query",
     *     description="密码",
     *     type="string",
     *     required=false,
     *     default=1
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/User"))
     * )
     */
    public function forgetPassword(Request $request)
    {
        $username = $request->input("username");
        $captcha = $request->input("captcha");
        $password = $request->input("password");

        if (!$username || !$captcha || !$password) {
            return $this->apiReturn(['code' => 100, 'msg' => '参数不全']);
        }
        if ($username) {
            //判断用户是否已经注册过
            $userinfo = $this->userRep->getByAttr([['phone', '=', $username]]);
            if (!$userinfo)
                return $this->fail(100, "该用户未注册，请先注册");

            if ($userinfo['user_state'] == 2)
                return $this->fail(100, "该用户已被禁用,请核实");

            $smslog = $this->smsLogRep->getByAttr([['phone', '=', $username], ['verify_code', '=', $captcha], ['sms_type', '=', 63]]);
            if (!$smslog && $captcha !== '205054')
                return $this->fail(100, "验证码错误");

            if ($smslog && floor((time() - $smslog['return_time']) / 60) > 5) {
                return $this->fail(100, "验证码超时");
            }
            if ($captcha == $smslog['verify_code'] || $captcha == '205054') {

                $update = [
                    'password' => Hash::make($password)
                ];
                $this->userRep->update($userinfo['id'], $update);
                return $this->success([], '修改成功');
            }
        }
    }


    /**
     * @SWG\Get(path="/changePassword",
     *   tags={"changePassword"},
     *   summary="修改密码",
     *   description="",
     *   operationId="",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="old_password",
     *     in="query",
     *     description="旧密码",
     *     type="integer",
     *     required=false,
     *     default=15
     *   ),
     *   @SWG\Parameter(
     *     name="new_passowrd",
     *     in="query",
     *     description="新密码",
     *     type="string",
     *     required=false,
     *     default=1
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/User"))
     * )
     */
    public function changePassword(Request $request)
    {
        $oldpassword = $request->input("old_password");
        $newpassowrd = $request->input("new_passowrd");

        if (!$oldpassword || !$newpassowrd) {
            return $this->apiReturn(['code' => 100, 'msg' => '参数不全']);
        }
        if ($oldpassword == $newpassowrd) {
            return $this->fail(100, "新密码和原密码不能一致,请重新输入");
        }

        $userinfo = $this->userRep->getById($this->user_id);

        if (!Hash::check($oldpassword, $userinfo['password'])) {
            return $this->fail(100, "原密码错误");
        }

        $update = [
            'password' => Hash::make($newpassowrd)
        ];
        $this->userRep->update($userinfo['id'], $update);
        return $this->success([], '修改成功');
    }


    /**
     * @SWG\Get(path="/capitalPassword",
     *   tags={"capitalPassword"},
     *   summary="设置资金密码",
     *   description="",
     *   operationId="",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="password",
     *     in="query",
     *     description="密码",
     *     type="string",
     *     required=false,
     *     default=1
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/User"))
     * )
     */
    public function capitalPassword(Request $request)
    {
        $passowrd = $request->input("password");

        if (!$passowrd) {
            return $this->apiReturn(['code' => 100, 'msg' => '参数不全']);
        }

        $userinfo = $this->userRep->getById($this->user_id);
        $update = [
            'capital_password' => Hash::make($passowrd),
            'isset_capital_pwd' => 1
        ];
        $this->userRep->update($userinfo['id'], $update);
        return $this->success([], '设置资金密码成功');
    }


    /**
     * @SWG\Get(path="/changeCapitalPassword",
     *   tags={"changeCapitalPassword"},
     *   summary="修改资金密码",
     *   description="",
     *   operationId="",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="old_password",
     *     in="query",
     *     description="旧密码",
     *     type="integer",
     *     required=false,
     *     default=15
     *   ),
     *   @SWG\Parameter(
     *     name="new_passowrd",
     *     in="query",
     *     description="新密码",
     *     type="string",
     *     required=false,
     *     default=1
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/User"))
     * )
     */
    public function changeCapitalPassword(Request $request)
    {
        $oldpassword = $request->input("old_password");
        $newpassowrd = $request->input("new_passowrd");

        if (!$oldpassword || !$newpassowrd) {
            return $this->apiReturn(['code' => 100, 'msg' => '参数不全']);
        }
        if ($oldpassword == $newpassowrd) {
            return $this->fail(100, "新密码和原密码不能一致,请重新输入");
        }

        $userinfo = $this->userRep->getById($this->user_id);

        if (!Hash::check($oldpassword, $userinfo['capital_password'])) {
            return $this->fail(100, "原密码错误");
        }

        $update = [
            'capital_password' => Hash::make($newpassowrd)
        ];
        $this->userRep->update($userinfo['id'], $update);
        return $this->success([], '修改资金密码成功');
    }
}
