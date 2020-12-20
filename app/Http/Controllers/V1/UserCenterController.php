<?php
namespace App\Http\Controllers\V1;

use App\Repositories\SmsLogRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class UserCenterController extends ApiController
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
     * @SWG\Get(path="/getUserInfo",
     *   tags={"getUserInfo"},
     *   summary="获取个人信息",
     *   description="",
     *   operationId="",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="userid",
     *     in="query",
     *     description="用户id",
     *     type="integer",
     *     required=false,
     *     default=1
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/User"))
     * )
     */
    public function getUserInfo(Request $request)
    {
        $userid = $request->input("userid");

        if (!$userid) {
            return $this->apiReturn(['code' => 100, 'msg' => '参数不全']);
        }

        $userinfo = $this->userRep->getById($this->user_id);

        if ($userinfo['user_state'] == 2)
            return $this->fail(100, "该用户已被禁用,请核实");

        return $this->success($userinfo);
    }


    /**
     * @SWG\Get(path="/authentication",
     *   tags={"authentication"},
     *   summary="身份认证",
     *   description="",
     *   operationId="",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="imgurl",
     *     in="query",
     *     description="图1",
     *     type="integer",
     *     required=false,
     *     default=15
     *   ),
     *   @SWG\Parameter(
     *     name="imgurl2",
     *     in="query",
     *     description="图2",
     *     type="string",
     *     required=false,
     *     default=1
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/User"))
     * )
     */
    public function authentication(Request $request)
    {
        $imgurl = $request->input("imgurl");
        $imgurl2 = $request->input("imgurl2");

        if (!$imgurl || !$imgurl2) {
            return $this->apiReturn(['code' => 100, 'msg' => '参数不全']);
        }

        $userinfo = $this->userRep->getById($this->user_id);

        $update = [
            'imgurl' => $imgurl,
            'imgurl2' => $imgurl2,
        ];
        $this->userRep->update($userinfo['id'], $update);
        return $this->success([], '修改成功');
    }


}
