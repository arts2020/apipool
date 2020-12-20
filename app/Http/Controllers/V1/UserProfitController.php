<?php

namespace App\Http\Controllers\V1;

use App\Repositories\UserProfitRepository;
use Illuminate\Http\Request;

class UserProfitController extends ApiController
{
    protected $powerRep;

    public function __construct(Request $request, UserProfitRepository $profitRepository)
    {
        parent::__construct($request);
        $this->profitRep = $profitRepository;
    }

    /**
     * @SWG\Post(path="/getMyProfit",
     *   tags={"getMyProfit"},
     *   summary="我的收益",
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
    public function getMyProfit(Request $request)
    {
        $asset = $request->input('asset');
        if (!$asset) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数分类']);
        }

        $btcprofit = $this->profitRep->getProfit($this->user_id,$asset)??0;
        $ethprofit = $this->profitRep->getProfit($this->user_id,$asset)??0;
        $filecoinprofit = $this->profitRep->getProfit($this->user_id,$asset)??0;

        return $this->success(compact('btcprofit','ethprofit','filecoinprofit'));

    }


    public function getProfitList(Request $request)
    {
        $asset = $request->input('asset');
        if (!$asset) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数分类']);
        }

        $list = $this->profitRep->getProfitList($this->user_id,$asset);
        return $this->success($list);

    }

}