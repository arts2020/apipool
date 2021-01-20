<?php

namespace App\Http\Controllers\V1;

use App\Repositories\UserProfitRepository;
use App\Repositories\ProductTypeRepository;
use Illuminate\Http\Request;

class UserProfitController extends ApiController
{
    protected $profitRep;
    protected $typeRep;

    public function __construct(Request $request, UserProfitRepository $profitRepository,
                                ProductTypeRepository $productTypeRepository)
    {
        parent::__construct($request);
        $this->profitRep = $profitRepository;
        $this->typeRep = $productTypeRepository;
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
        $typeLists = $this->typeRep->getListWithProfit($this->user_id);

        if($typeLists->isNotEmpty()){
            foreach($typeLists as &$type){
                $type['total'] = array_sum(array_pluck ($type->profit,'count'));
                $type['withdrawal_amount'] = array_sum(array_pluck ($type->profit,'withdrawal_amount'));
                $type['frozen_amount'] = array_sum(array_pluck ($type->profit,'frozen_amount'));
                $type['today_amount'] = $type->share?$type->share->amount:0;
                $type['drawing_amount'] = array_sum(array_pluck ($type->trade,'amount'));
                unset($type->profit);
                unset($type->share);
                unset($type->trade);
            }
            unset($type);
        }

        return $this->success($typeLists);
    }


    public function getProfitList(Request $request)
    {
        $asset = $request->input('asset');
        if (!$asset) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数分类']);
        }

        $list = $this->profitRep->getProfitList($this->user_id,$asset);
        return $this->success(compact('asset','list'));

    }

}