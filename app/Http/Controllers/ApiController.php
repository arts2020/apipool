<?php

namespace App\Http\Controllers;

use App\Models\Base;
use App\Models\Hospital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;


class ApiController extends Controller
{
    protected $user_id = 0;
    protected $user_type = 0;
    protected $open_id = '';
    protected $sessionUser = '';

    public $redis;

    public function __construct(Request $request)
    {
        $token = $request->input('token');
        if ($token) {
            $baseModel = new Base();
            $sessionData = $baseModel->get($token);
            $this->sessionUser = $sessionData;
            $this->user_id = $sessionData['userid'];
        }
    }

    public $returnData = [
        'code' => '200',
        'msg' => '',
        'data' => '',
    ];

    /**
     * 成功返回消息
     * @param string $code
     * @param string $msg
     * @param string $data
     * @return array
     * @author alan
     */
    public function success($data = [], $msg = '获取成功')
    {
        $msg = [
            'code' => 200,
            'msg' => $msg,
            'data' => $data,
        ];
        return $this->apiReturn($msg);
    }

    /**
     * 失败返回消息
     * @param string $code
     * @param string $msg
     * @param string $data
     * @return array
     * @author alan
     */
    public function fail($code = '100', $msg = '获取失败', $data = '')
    {
        $msg = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
        return $this->apiReturn($msg);
    }

    /**
     * @todo 设置缓存
     */
    public function setCache($key, $data)
    {
        $key = env("REDIS_PREFIX") . $key;
        $this->redis->set($key, $data);
        $this->redis->expire($key, 86400);
    }

    /**
     * @todo 得到缓存
     */
    public function getCache($key)
    {
        $key = env("REDIS_PREFIX") . $key;
        $this->redis = Redis::connection('default');
        $data = $this->redis->get($key);
        return $data;
    }

    public function checkToken(Request $request)
    {
        $token = $request->input('token');

        $base = new Base();
        $result = $base->keyExists($token);
        if ($result) {
            return $this->success();
        } else {
            return $this->fail();
        }
    }

    /**
     * @SWG\Post(path="/checkTokenExist",
     *   tags={"checkTokenExist"},
     *   summary="",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
     *     name="token",
     *     in="query",
     *     description="",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="检查token是否存在", @SWG\Schema(ref="#/definitions/Zhubo"))
     * )
     */
    public function checkTokenExist(Request $request)
    {
        $token = $request->input('token');

        $base = new Base();
        $result = $base->keyExists($token);
        if ($result) {
            return $this->success();
        } else {
            return $this->fail();
        }
    }

}
