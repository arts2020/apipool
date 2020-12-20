<?php

namespace App\Http\Controllers\V1;

use App\Repositories\UserPowerRepository;
use Illuminate\Http\Request;

class UserPowerController extends ApiController
{
    protected $powerRep;

    public function __construct(Request $request, UserPowerRepository $powerRepository)
    {
        parent::__construct($request);
        $this->powerRep = $powerRepository;
    }

    /**
     * @SWG\Post(path="/getPower",
     *   tags={"getPower"},
     *   summary="我的算力",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *  @SWG\Parameter(
     *     name="asset",
     *     in="query",
     *     description="分类",
     *     required=true,
     *     type="integer"
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/Product"))
     * )
     */
    public function getPower(Request $request)
    {
        $asset = $request->input('asset');
        if (!$asset) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数分类']);
        }

        $total = $this->powerRep->getTotalPower($this->user_id,$asset)??0;
        $valid = $this->powerRep->getValidPower($this->user_id,$asset)??0;
        $powerLists = $this->powerRep->getPowerList($this->user_id,$asset);

        return $this->success(compact('total','valid','powerLists'));

    }

}