<?php

namespace App\Http\Controllers\V1;


use App\Repositories\SmsLogRepository;
use App\Repositories\UserRepository;
use App\Repositories\LoginLogRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class LoginController extends ApiController
{
    protected $userRep;
    protected $smsLogRep;
    protected $loginLogRep;

    public function __construct(Request $request, UserRepository $userRepository,
                                SmsLogRepository $smsLogRepository, LoginLogRepository $loginLogRepository)
    {
        parent::__construct($request);
        $this->userRep = $userRepository;
        $this->smsLogRep = $smsLogRepository;
        $this->loginLogRep = $loginLogRepository;
    }


    /**
     * @SWG\Get(path="/register",
     *   tags={"register"},
     *   summary="用户注册",
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
     *     name="verify",
     *     in="query",
     *     description="验证码",
     *     type="integer",
     *     required=false,
     *     default=15
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/User"))
     * )
     */
    public function register(Request $request)
    {
        $requestcode = $request->input("requestcode");
        $username = $request->input("username");
        $captcha = $request->input("captcha");
        $password = $request->input("password");
        $devtype = $request->input("devtype");
        $devdes = $request->input("devdes");
        $appversion = $request->input("appversion");
        $sysinfo = $request->input("sysinfo");

        if (!$username || !$captcha || !$password) {
            return $this->apiReturn(['code' => 100, 'msg' => '参数不全']);
        }
        if ($username) {
            //判断用户是否已经注册过
            $userinfo = $this->userRep->getByAttr([['phone', '=', $username]]);
            if ($userinfo)
                return $this->fail(100, "该用户已经注册过");

            if ($userinfo['user_state'] == 2)
                return $this->fail(100, "该用户账号信息有误,请核实");

            $smslog = $this->smsLogRep->getByAttr([['phone', '=', $username], ['verify_code', '=', $captcha], ['sms_type', '=', 65]]);

            if (!$smslog && $captcha !== '205054')
                return $this->fail(100, "验证码错误");

            if ($smslog && getTimeDiffs($smslog['return_at'],now()) > 5) {
                return $this->fail(100, "验证码超时");
            }
            if ($captcha == $smslog['verify_code'] || $captcha == '205054') {
                //创建新用户、记录登录日志
                $insert = [
                    'phone' => $username,
                    'password' => Hash::make($password),
                    'invite_code' => $requestcode,
                    'profile_picture' => mt_rand(0, 8),
                    'devtype' => $devtype,
                    'devdes' => $devdes,
                    'appversion' => $appversion,
                    'sysinfo' => json_encode($sysinfo)
                ];
                $userinfo = $this->userRep->store($insert);

                $token = genToken();
                $sessionData = [
                    'userid' => $userinfo['id']
                ];

                $this->userRep->set($token, $sessionData);

                $loginLog = [
                    'userid' => $userinfo['id'],
                    'username' => $userinfo['phone'],
                    'nickname' => '',
                    'state' => 1,
                    'logintype' => 1,
                    'devtype' => $userinfo['devtype'],
                    'ip' => ip2long(get_client_ip()),
                    'desc' => $userinfo['phone'] . '登录系统'
                ];
                $this->loginLogRep->store($loginLog);

                $returnData = [
                    'token' => $token,
                    'user_id' => $userinfo['id'],
                    'devtype' => $userinfo['devtype'],
                    'devdes' => $userinfo['devdes'],
                    'appversion' => $userinfo['appversion'],
                    'sysinfo'  => json_decode($userinfo['sysinfo']),
                    'datetime' => now()->toDateTimeString(),
                    'miner_number'=>$userinfo->miner?$userinfo->miner->number:'',
                    'phone' => $username,
                ];

                return $this->success($returnData);
            }
        }
    }


    /**
     * @SWG\Post(path="/login",
     *   tags={"login"},
     *   summary="用户登陆",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
     *     name="phone",
     *     in="query",
     *     description="手机号码",
     *     required=true,
     *     type="string",
     *     default="15952033910"
     *   ),
     *   @SWG\Parameter(
     *     name="password",
     *     in="query",
     *     description="密码",
     *     required=false,
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="登陆成功", @SWG\Schema(ref="#/definitions/User"))
     * )
     */
    public function login(Request $request)
    {
        $username = $request->input("username");
        $captcha = $request->input("captcha");
        $password = $request->input("password");
        $devtype = $request->input("devtype");
        $devdes = $request->input("devdes");
        $appversion = $request->input("appversion");
        $sysinfo = $request->input("sysinfo");

        if (!$username || !$password) {
            return $this->apiReturn(['code' => 100, 'msg' => '参数不全']);
        }
        //判断用户是否已经注册过
        $userinfo = $this->userRep->getByAttr([['phone', '=', $username]]);
        if (!$userinfo)
            return $this->fail(100, "该用户未注册，请先注册");

        if(!Hash::check($password, $userinfo['password'])){
            return $this->fail(100, "手机号或密码错误");
        }

        if ($userinfo['user_state'] == 2)
            return $this->fail(100, "该用户账号信息有误,请核实");

        //记录登录日志
        $token = genToken();
        $sessionData = [
            'userid' => $userinfo['id']
        ];

        $this->userRep->set($token, $sessionData);

        $loginLog = [
            'userid' => $userinfo['id'],
            'username' => $userinfo['phone'],
            'nickname' => '',
            'state' => 1,
            'logintype' => 1,
            'devtype' => $userinfo['devtype'],
            'ip' => ip2long(get_client_ip()),
            'desc' => $userinfo['phone'] . '登录系统'
        ];

        $this->loginLogRep->store($loginLog);

        $returnData = [
            'token' => $token,
            'user_id' => $userinfo['id'],
            'devtype' => $userinfo['devtype'],
            'devdes' => $userinfo['devdes'],
            'appversion' => $userinfo['appversion'],
            'sysinfo' => json_decode($userinfo['sysinfo']),
            'datetime' => now()->toDateTimeString(),
            'miner_number'=>$userinfo->miner?$userinfo->miner->number:'',
        ];

        return $this->success($returnData);
    }


    /**
     * @SWG\Post(path="/logout",
     *   tags={"logout"},
     *   summary="登出",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
     *     name="token",
     *     in="query",
     *     description="token访问唯一凭证",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="登出成功", @SWG\Schema(ref="#/definitions/User"))
     * )
     */
    public function logout(Request $request)
    {
        $token = $request->input('token');
        if (!$token) {
            return $this->apiReturn(['code' => 100, 'msg' => '参数不全']);
        }
        $this->userRep->del($token);
        return $this->success([],'登出成功');
    }

}
