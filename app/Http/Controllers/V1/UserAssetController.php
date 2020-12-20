<?php

namespace App\Http\Controllers\V1;

use App\Repositories\UserAssetRepository;
use App\Repositories\UserTradeRepository;
use Illuminate\Http\Request;

class UserAssetController extends ApiController
{
    protected $assetRep;
    protected $tradeRep;

    public function __construct(Request $request, UserAssetRepository $assetRepository,
                                UserTradeRepository $tradeRepository)
    {
        parent::__construct($request);
        $this->assetRep = $assetRepository;
        $this->tradeRep = $tradeRepository;
    }

    /**
     * @SWG\Post(path="/getMyAsset",
     *   tags={"getMyAsset"},
     *   summary="我的钱包",
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
    public function getMyAsset(Request $request)
    {
        $lists = $this->assetRep->getAssetList($this->user_id);
        return $this->success($lists);

    }

    /**
     * 获取交易记录
     * @param Request $request
     * @return array|mixed
     */
    public function getTransferList(Request $request)
    {
        $asset = $request->input('asset');
        if (!$asset) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数分类']);
        }
        $total = array_sum(array_pluck($this->tradeRep->getTradeList($this->user_id,$asset),'amount'));
        $cny = $total?turnCny($total):0;
        $list = $this->tradeRep->getTradeList($this->user_id,$asset);
        return $this->success(compact('total','list'));

    }

    public function transfer(Request $request)
    {
        $asset = $request->input('asset');
        if (!$asset) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数分类']);
        }
        $amount = $request->input('amount');
        if (!$amount) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数交易数量']);
        }
        $to_address = $request->input('to_address');
        if (!$to_address) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数转账地址']);
        }
        $assetInfo = $this->assetRep->getAssetInfo($this->user_id,$asset);
        $insert = [
            'userid'=>$this->user_id,
            'asset'=>$asset,
            'amount'=>$amount,
            'type'=>'转账',
            'from_address'=>$assetInfo->address,
            'to_address'=>$to_address,
            'state'=> 0
        ];
        $res = $this->tradeRep->store($insert);
        return $this->success($res);
    }

}